<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserAccess;
use App\Models\UserControl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function store(Request $request)
    {
        // ✅ Validate request
        $request->validate([
            'fullname' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'username' => 'required|string|max:255|unique:users,username',
            'password' => 'required|string|min:6',
            'department' => 'nullable|string|max:255',
            'role' => 'required|string',
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
            'modules' => 'required|array|min:1',
            'modules.*.module_name' => 'required|string',
            'modules.*.parent_module' => 'nullable|string',
            'modules.*.can_view' => 'required|boolean',
            'modules.*.can_add' => 'required|boolean',
            'modules.*.can_edit' => 'required|boolean',
            'modules.*.can_delete' => 'required|boolean',
        ]);

        DB::beginTransaction();

        try {
            // 1️⃣ Create user
            $user = User::create([
                'fullname' => $request->fullname,
                'email' => $request->email,
                'username' => $request->username,
                'password' => $request->password, // hashed automatically
                'department' => $request->department ?? null,
            ]);

            // 2️⃣ Create user control
            $control = UserControl::create([
                'user_id' => $user->id,
                'role' => $request->role,
                'status' => $request->status,
            ]);

            // 3️⃣ Create user access modules
            foreach ($request->modules as $module) {
                UserAccess::create([
                    'user_id' => $user->id,
                    'module_name' => $module['module_name'],
                    'parent_module' => $module['parent_module'] ?? null,
                    'can_view' => $module['can_view'],
                    'can_add' => $module['can_add'],
                    'can_edit' => $module['can_edit'],
                    'can_delete' => $module['can_delete'],
                ]);
            }

            DB::commit();

            // 4️⃣ Fetch all modules for nesting
            $allModules = UserAccess::where('user_id', $user->id)
                ->get(['module_name', 'parent_module', 'can_view', 'can_add', 'can_edit', 'can_delete'])
                ->toArray();

            $modulesNested = [];
            $map = [];

            // Map each module
            foreach ($allModules as $mod) {
                $mod['children'] = [];
                $map[$mod['module_name']] = $mod;
            }

            // Nest children under parent
            foreach ($allModules as $mod) {
                if ($mod['parent_module']) {
                    if (isset($map[$mod['parent_module']])) {
                        $map[$mod['parent_module']]['children'][] = $mod;
                    }
                } else {
                    $modulesNested[] = $mod;
                }
            }

            // Remove empty children arrays
            function cleanChildren($modules)
            {
                foreach ($modules as &$mod) {
                    if (empty($mod['children'])) {
                        unset($mod['children']);
                    } else {
                        $mod['children'] = cleanChildren($mod['children']);
                    }
                }

                return $modules;
            }

            $modulesNested = cleanChildren($modulesNested);

            // 5️⃣ Generate token
            $token = $user->createToken('api_token')->plainTextToken;

            $response = [
                'user' => [
                    'id' => $user->id,
                    'fullname' => $user->fullname,
                    'username' => $user->username,
                    'role' => $control->role,
                    'status' => $control->status,
                    'modules' => $modulesNested,
                ],
                'access_token' => $token,
                'token_type' => 'Bearer',
            ];

            return response()->json([
                'message' => 'User created successfully',
                'data' => $response,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to create user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getAllUsers()
    {
        $users = User::with('control', 'access')->get();

        // Recursive function to fetch children of any depth
        $buildTree = function ($allModules, $parentName) use (&$buildTree) {
            $children = $allModules->where('parent_module', $parentName)->values();

            return $children->map(function ($child) use ($allModules, $buildTree) {
                $childArr = [
                    'module_name' => $child->module_name,
                    'parent_module' => $child->parent_module,
                    'can_view' => $child->can_view,
                    'can_add' => $child->can_add,
                    'can_edit' => $child->can_edit,
                    'can_delete' => $child->can_delete,
                ];

                // 🔁 Recursive: get sub-children of this child
                $subChildren = $buildTree($allModules, $child->module_name);
                if ($subChildren->count() > 0) {
                    $childArr['children'] = $subChildren;
                }

                return $childArr;
            });
        };

        $response = $users->map(function ($user) use ($buildTree) {
            $allModules = $user->access;

            // Top-level modules (no parent_module)
            $parents = $allModules->whereNull('parent_module')->values();

            $modules = $parents->map(function ($parent) use ($allModules, $buildTree) {
                $parentArr = [
                    'module_name' => $parent->module_name,
                    'parent_module' => $parent->parent_module,
                    'can_view' => $parent->can_view,
                    'can_add' => $parent->can_add,
                    'can_edit' => $parent->can_edit,
                    'can_delete' => $parent->can_delete,
                ];

                // 🔁 Fetch children recursively
                $children = $buildTree($allModules, $parent->module_name);
                if ($children->count() > 0) {
                    $parentArr['children'] = $children;
                }

                return $parentArr;
            });

            return [
                'id' => $user->id,
                'fullname' => $user->fullname,
                'username' => $user->username,
                'role' => $user->control->role ?? null,
                'status' => $user->control->status ?? null,
                'modules' => $modules,
            ];
        });

        return response()->json([
            'message' => 'Users fetched successfully',
            'data' => $response,
        ]);
    }

    public function getUserById($id)
    {
        $user = User::with('control', 'access')->find($id);

        if (! $user) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }

        // recursive builder
        $buildTree = function ($allModules, $parentName) use (&$buildTree) {
            $children = $allModules->where('parent_module', $parentName)->values();

            return $children->map(function ($child) use ($allModules, $buildTree) {
                $childArr = [
                    'module_name' => $child->module_name,
                    'parent_module' => $child->parent_module,
                    'can_view' => $child->can_view,
                    'can_add' => $child->can_add,
                    'can_edit' => $child->can_edit,
                    'can_delete' => $child->can_delete,
                ];

                // recursion
                $subChildren = $buildTree($allModules, $child->module_name);
                if ($subChildren->count() > 0) {
                    $childArr['children'] = $subChildren;
                }

                return $childArr;
            });
        };

        $parents = $user->access->whereNull('parent_module')->values();

        $modules = $parents->map(function ($parent) use ($user, $buildTree) {
            $parentArr = [
                'module_name' => $parent->module_name,
                'parent_module' => $parent->parent_module,
                'can_view' => $parent->can_view,
                'can_add' => $parent->can_add,
                'can_edit' => $parent->can_edit,
                'can_delete' => $parent->can_delete,
            ];

            $children = $buildTree($user->access, $parent->module_name);
            if ($children->count() > 0) {
                $parentArr['children'] = $children;
            }

            return $parentArr;
        });

        $response = [
            'id' => $user->id,
            'fullname' => $user->fullname,
            'email' => $user->email,
            'department' => $user->department,
            'username' => $user->username,
            'role' => $user->control->role ?? null,
            'status' => $user->control->status ?? null,
            'modules' => $modules,
        ];

        return response()->json([
            'message' => 'User fetched successfully',
            'data' => $response,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'fullname' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:users,email,'.$id,
            'username' => 'sometimes|string|max:255|unique:users,username,'.$id,
            'department' => 'sometimes|string|max:255',
            'role' => 'sometimes|string',
            'status' => ['sometimes', Rule::in(['Active', 'Inactive'])],
            'modules' => 'sometimes|array',
            'modules.*.module_name' => 'required|string',
            'modules.*.can_view' => 'required|boolean',
            'modules.*.can_add' => 'required|boolean',
            'modules.*.can_edit' => 'required|boolean',
            'modules.*.can_delete' => 'required|boolean',
        ]);

        DB::beginTransaction();
        try {
            $user = User::findOrFail($id);

            // 1️⃣ Update user info (remove password completely)
            $user->update([
                'fullname' => $request->fullname ?? $user->fullname,
                'email' => $request->email ?? $user->email,
                'username' => $request->username ?? $user->username,
                'department' => $request->department ?? $user->department,
            ]);

            // 2️⃣ Update control
            if ($request->has('role') || $request->has('status')) {
                $control = $user->control;
                $control->update([
                    'role' => $request->role ?? $control->role,
                    'status' => $request->status ?? $control->status,
                ]);
            }

            // 3️⃣ Update modules
            if ($request->has('modules')) {
                foreach ($request->modules as $mod) {
                    UserAccess::updateOrCreate(
                        ['user_id' => $user->id, 'module_name' => $mod['module_name']],
                        [
                            'parent_module' => $mod['parent_module'] ?? null,
                            'can_view' => (int) $mod['can_view'],
                            'can_add' => (int) $mod['can_add'],
                            'can_edit' => (int) $mod['can_edit'],
                            'can_delete' => (int) $mod['can_delete'],
                        ]
                    );
                }
            }

            DB::commit();

            // 4️⃣ Return updated user with nested modules
            $modules = $user->access()->get()->toArray();

            // Recursive tree builder
            $buildTree = function ($elements, $parent = null) use (&$buildTree) {
                $branch = [];
                foreach ($elements as $element) {
                    if ($element['parent_module'] === $parent) {
                        $children = $buildTree($elements, $element['module_name']);
                        if ($children) {
                            $element['children'] = $children;
                        }
                        $branch[] = $element;
                    }
                }

                return $branch;
            };

            $nestedModules = $buildTree($modules);

            $response = [
                'id' => $user->id,
                'fullname' => $user->fullname,
                'email' => $user->email,
                'username' => $user->username,
                'role' => $user->control->role ?? null,
                'department' => $user->department,
                'status' => $user->control->status ?? null,
                'modules' => $nestedModules,
            ];

            return response()->json([
                'message' => 'User updated successfully',
                'data' => $response,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to update user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteUser($id)
    {
        DB::beginTransaction();

        try {
            $user = User::findOrFail($id);

            // Delete related records using relationships
            $user->access()->delete();
            $user->control()->delete();

            $user->delete();

            DB::commit();

            return response()->json([
                'message' => 'User deleted successfully',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to delete user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getUserModules(Request $request)
    {
        $user = User::with('modules')->find($request->user()->id);

        // Optional: format modules like your frontend expects
        $modules = $user->modules->map(function ($m) {
            return [
                'module_name' => $m->module_name,
                'parent_module' => $m->parent_module,
                'can_add' => (int) $m->can_add,
                'can_edit' => (int) $m->can_edit,
                'can_view' => (int) $m->can_view,
                'can_delete' => (int) $m->can_delete,
            ];
        });

        $user->modules = $modules;

        return response()->json(['user' => $user]);
    }
}

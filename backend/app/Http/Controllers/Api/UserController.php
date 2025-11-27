<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{

public function store(Request $request)
{
    DB::beginTransaction();

    try {
        // ✅ Validate input
        $validated = $request->validate([
            'fullname' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'department' => 'nullable|string|max:255',
            'role' => ['required', Rule::in(['Administrator', 'Staff', 'Manager', 'General Manager'])],
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
            'modules' => 'nullable|array',
        ]);

        // ✅ Create user
        $user = User::create([
            'fullname' => $validated['fullname'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'department' => $validated['department'] ?? null,
            'role' => $validated['role'],
            'status' => $validated['status'],
        ]);

        // ✅ Assign module access recursively
        if (!empty($validated['modules'])) {
            foreach ($validated['modules'] as $module) {
                $this->createModuleAccess($user->user_id, $module);
            }
        }

        DB::commit();

        // ✅ Load modules for response
        $user->load('accessModules');

        // ✅ Recursive formatter for nested modules
        $buildTree = function ($modules, $parent = null) use (&$buildTree) {
            $children = $modules->where('parent_module', $parent);
            return $children->map(function ($mod) use ($modules, $buildTree) {
                $data = [
                    'module_name' => $mod->module_name,
                    'parent_module' => $mod->parent_module,
                    'can_view' => (bool) $mod->can_view,
                    'can_add' => (bool) $mod->can_add,
                    'can_edit' => (bool) $mod->can_edit,
                    'can_delete' => (bool) $mod->can_delete,
                ];

                $subModules = $buildTree($modules, $mod->module_name);
                if ($subModules->isNotEmpty()) {
                    $data['children'] = $subModules;
                }

                return $data;
            })->values();
        };

        $modulesTree = $buildTree($user->accessModules);

        // ✅ Return complete user data with modules
        return response()->json([
            'success' => true,
            'message' => 'User created successfully.',
            'user' => [
                'user_id' => $user->user_id,
                'fullname' => $user->fullname,
                'email' => $user->email,
                'username' => $user->username,
                'department' => $user->department,
                'role' => $user->role,
                'status' => $user->status,
                'modules' => $modulesTree,
            ],
        ], 201);

    } catch (\Throwable $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage(),
        ], 500);
    }
}

private function createModuleAccess($userId, $module, $parentModule = null)
{
    // ✅ Create the module record
    $access = UserAccess::create([
        'user_id' => $userId,
        'module_name' => $module['module_name'],
        'parent_module' => $module['parent_module'] ?? $parentModule,
        'can_view' => $module['can_view'] ?? false,
        'can_add' => $module['can_add'] ?? false,
        'can_edit' => $module['can_edit'] ?? false,
        'can_delete' => $module['can_delete'] ?? false,
    ]);

    // ✅ Recursively create child modules
    if (!empty($module['children'])) {
        foreach ($module['children'] as $child) {
            $this->createModuleAccess($userId, $child, $module['module_name']);
        }
    }
}



public function getAllUsers(Request $request)
{
    try {
        $search = $request->input('search');
        $role = $request->input('role');
        $status = $request->input('status');
        $department = $request->input('department');
        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'user_id');
        $sortDir = $request->input('sort_dir', 'asc'); // newest first by default

        // ✅ Eager load accessModules relationship
        $query = User::with('accessModules')
            ->orderBy($sortBy, $sortDir);

        // 🔍 Search filter
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('fullname', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('department', 'like', "%{$search}%")
                  ->orWhere('role', 'like', "%{$search}%");
            });
        }

        // 🎯 Optional filters
        if (!empty($role)) {
            $query->where('role', $role);
        }

        if (!empty($status)) {
            $query->where('status', $status);
        }

        if (!empty($department)) {
            $query->where('department', $department);
        }

        // 🧮 Paginate results
        $users = $query->paginate($perPage);

        // 🔁 Recursive module tree builder
        $buildTree = function ($allModules, $parentName = null) use (&$buildTree) {
            $children = $allModules->where('parent_module', $parentName)->values();

            return $children->map(function ($child) use ($allModules, $buildTree) {
                $node = [
                    'module_name' => $child->module_name,
                    'parent_module' => $child->parent_module,
                    'can_view' => (bool) $child->can_view,
                    'can_add' => (bool) $child->can_add,
                    'can_edit' => (bool) $child->can_edit,
                    'can_delete' => (bool) $child->can_delete,
                ];

                $subChildren = $buildTree($allModules, $child->module_name);
                if ($subChildren->isNotEmpty()) {
                    $node['children'] = $subChildren;
                }

                return $node;
            });
        };

        // 🧩 Format each user for frontend
        $formattedUsers = $users->getCollection()->map(function ($user) use ($buildTree) {
            $allModules = $user->accessModules;
            $modules = $buildTree($allModules, null);

            return [
                'user_id' => $user->user_id,
                'fullname' => $user->fullname,
                'username' => $user->username,
                'email' => $user->email,
                'department' => $user->department,
                'role' => $user->role,
                'status' => $user->status,
                'modules' => $modules,
            ];
        });

        // 🔄 Return paginated response
        return response()->json([
            'message' => 'Users fetched successfully',
            'data' => $formattedUsers,
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to fetch users',
            'message' => $e->getMessage(),
        ], 500);
    }
}




   public function getUserById($id)
{
    try {
        // ✅ Eager load accessModules
        $user = User::with('accessModules')->where('user_id', $id)->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }

        // 🔁 Recursive module builder
        $buildTree = function ($allModules, $parentName = null) use (&$buildTree) {
            $children = $allModules->where('parent_module', $parentName)->values();

            return $children->map(function ($child) use ($allModules, $buildTree) {
                $node = [
                    'module_name' => $child->module_name,
                    'parent_module' => $child->parent_module,
                    'can_view' => (bool)$child->can_view,
                    'can_add' => (bool)$child->can_add,
                    'can_edit' => (bool)$child->can_edit,
                    'can_delete' => (bool)$child->can_delete,
                ];

                $subChildren = $buildTree($allModules, $child->module_name);
                if ($subChildren->isNotEmpty()) {
                    $node['children'] = $subChildren;
                }

                return $node;
            });
        };

        // 🧩 Build full module hierarchy
        $modules = $buildTree($user->accessModules, null);

        // ✅ Prepare formatted response
        $response = [
            'user_id' => $user->user_id,
            'fullname' => $user->fullname,
            'email' => $user->email,
            'username' => $user->username,
            'department' => $user->department,
            'role' => $user->role,
            'status' => $user->status,
            'modules' => $modules,
        ];

        return response()->json([
            'message' => 'User fetched successfully',
            'data' => $response,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to fetch user',
            'message' => $e->getMessage(),
        ], 500);
    }
}


  public function update(Request $request, $id)
{
    $request->validate([
        'fullname' => 'sometimes|string|max:255',
        'email' => 'sometimes|email|max:255|unique:users,email,' . $id . ',user_id',
        'username' => 'sometimes|string|max:255|unique:users,username,' . $id . ',user_id',
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

        // ✅ 1. Update user info (no password involved)
        $user->update([
            'fullname' => $request->fullname ?? $user->fullname,
            'email' => $request->email ?? $user->email,
            'username' => $request->username ?? $user->username,
            'department' => $request->department ?? $user->department,
            'role' => $request->role ?? $user->role,
            'status' => $request->status ?? $user->status,
        ]);

        // ✅ 2. Update or create access modules
        if ($request->has('modules')) {
            foreach ($request->modules as $mod) {
                UserAccess::updateOrCreate(
                    [
                        'user_id' => $user->user_id,
                        'module_name' => $mod['module_name'],
                    ],
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

        // ✅ 3. Fetch updated access modules
        $modules = $user->accessModules()->get()->toArray();

        // Recursive builder for nested structure
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

        // ✅ 4. Response payload
        $response = [
            'user_id' => $user->user_id,
            'fullname' => $user->fullname,
            'email' => $user->email,
            'username' => $user->username,
            'department' => $user->department,
            'role' => $user->role,
            'status' => $user->status,
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

        // ✅ Delete related access modules
        $user->accessModules()->delete();

        // ✅ Then delete the user itself
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
    try {
        $authUser = $request->user();

        if (!$authUser) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized user or invalid token',
            ], 401);
        }

        // ✅ Use user_id instead of id
        $user = User::with('accessModules')->where('user_id', $authUser->user_id)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }
           $deptHead = DB::table('tbl_depthead as d')
        ->leftJoin('users as u', 'u.department', '=', 'd.department') 
        ->select('d.depthead_name')
        ->first();


    $deptHeadName = $deptHead->depthead_name ?? null;

        // ✅ Format module data
        $modules = $user->accessModules->map(function ($m) {
            return [
                'module_name'   => $m->module_name,
                'parent_module' => $m->parent_module,
                'can_add'       => (bool) $m->can_add,
                'can_edit'      => (bool) $m->can_edit,
                'can_view'      => (bool) $m->can_view,
                'can_delete'    => (bool) $m->can_delete,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'User modules fetched successfully',
            'user' => [
                'user_id'   => $user->user_id,
                'fullname'  => $user->fullname,
                'email'     => $user->email,
                'username'  => $user->username,
                'department' =>$user->department,
                'depthead_name' =>$deptHeadName,
                'role'      => $user->role,
                'modules'   => $modules,
            ],
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch user modules',
            'error' => $e->getMessage(),
        ], 500);
    }
}



}

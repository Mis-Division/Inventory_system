<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserAccess; // ✅ required for module access
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    /**
     * Login user and return access token + control + modules
     */
 public function login(Request $request)
{
    $request->validate([
        'username' => 'required|string',
        'password' => 'required|string',
    ]);

    // Trim inputs to avoid accidental spaces
    $username = trim($request->username);
    $password = trim($request->password);

    // 1️⃣ Find user by username
    $user = User::where('username', $username)->first();

    // Debug info
    if (!$user) {
        \Log::info("Login failed: user not found", ['username' => $username]);
        return response()->json([
            'message' => 'Invalid username or password. (User not found)'
        ], 401);
    }

    // 2️⃣ Check password
    if (! Hash::check($password, $user->password)) {
        \Log::info("Login failed: password mismatch", [
            'username' => $username,
            'db_password' => $user->password, // hashed
            'input_password' => $password,
        ]);
        return response()->json([
            'message' => 'Invalid username or password. (Password mismatch)'
        ], 401);
    }

    // 3️⃣ Check if account is active
    if ($user->status !== 'Active') {
        \Log::info("Login failed: account inactive", [
            'username' => $username,
            'status' => $user->status
        ]);
        return response()->json([
            'message' => 'Your account is deactivated. Please contact ETSD for activation.',
        ], 403);
    }

   $deptHead = DB::table('tbl_depthead as d')
        ->leftJoin('users as u', 'u.department', '=', 'd.department') 
        ->select('d.depthead_name')
        ->first();


    $deptHeadName = $deptHead->depthead_name ?? null;
    // 4️⃣ Fetch all access modules for this user
    $allModules = UserAccess::where('user_id', $user->user_id)
        ->get(['module_name', 'parent_module', 'can_view', 'can_add', 'can_edit', 'can_delete'])
        ->toArray();

    // 5️⃣ Build nested tree
    $modulesByName = [];
    foreach ($allModules as $mod) {
        $mod['children'] = [];
        $modulesByName[$mod['module_name']] = $mod;
    }

    $rootModules = [];
    foreach ($allModules as $mod) {
        if ($mod['parent_module'] && isset($modulesByName[$mod['parent_module']])) {
            $modulesByName[$mod['parent_module']]['children'][] = &$modulesByName[$mod['module_name']];
        } else {
            $rootModules[] = &$modulesByName[$mod['module_name']];
        }
    }

    $modulesNested = $this->cleanChildren($rootModules);

    // 6️⃣ Generate token
    $token = $user->createToken('api_token')->plainTextToken;

    // 7️⃣ Return response
    return response()->json([
        'message' => 'Login successful',
        'data' => [
            'user' => [
                'user_id' => $user->user_id,
                'fullname' => $user->fullname,
                'username' => $user->username,
                'department' => $user->department,
                'depthead_name' =>$deptHeadName,
                'role' => $user->role,
                'status' => $user->status,
                'modules' => $modulesNested,
            ],
            'access_token' => $token,
            'token_type' => 'Bearer',
        ],
    ]);
}

/**
 * Recursively remove empty children arrays
 */
private function cleanChildren(array $modules)
{
    foreach ($modules as &$mod) {
        if (empty($mod['children'])) {
            unset($mod['children']);
        } else {
            $mod['children'] = $this->cleanChildren($mod['children']);
        }
    }

    return $modules;
}


    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }
}

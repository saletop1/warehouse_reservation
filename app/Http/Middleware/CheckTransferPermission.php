<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckTransferPermission
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        $allowedRoles = ['admin', 'manager', 'supervisor'];
        $restrictedUsers = ['viewer@example.com', 'guest@example.com'];

        $hasPermission = $user->hasAnyRole($allowedRoles) &&
                        !in_array($user->email, $restrictedUsers);

        if (!$hasPermission) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to perform transfer operation'
                ], 403);
            }

            return redirect()->back()
                ->with('error', 'You do not have permission to perform this action.');
        }

        return $next($request);
    }
}

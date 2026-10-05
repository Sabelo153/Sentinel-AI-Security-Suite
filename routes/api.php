<?php

declare(strict_types=1);

use Sentinel\Core\Response;
use Sentinel\Core\Container;
use Sentinel\Core\Request;
use Sentinel\Core\Router;

return static function (Router $router, Container $container): void {
	// Liveness never touches a dependency. It is safe for a load balancer to poll.
	$router->get('/healthz', static fn (): Response => Response::json(['status' => 'ok', 'service' => 'sentinel']));
	// Readiness verifies the runtime database without exposing its host, schema,
	// credentials, or raw driver errors.
	$router->get('/readyz', static function () use ($container): Response {
		try {
			$container->make('database')->fetch('SELECT 1 AS ready');
			return Response::json(['status' => 'ready']);
		} catch (\PDOException|\Sentinel\Core\HttpException) {
			return Response::json(['status' => 'unavailable'], 503);
		}
	});
	$router->get('/api/v1/status', static fn (): Response => Response::json(['status' => 'ok']));
	$router->get('/api/v1/plans', static fn (): Response => Response::json(['data' => \Sentinel\Services\SubscriptionPolicy::plans()]));
	$router->post('/api/v1/auth/signup', static function (Request $request) use ($container, $router): Response {
		return Response::json(['data' => $container->make('signup_service')->signup($request->json(), $router->securityMetadata())], 201);
	});
	$router->post('/api/v1/auth/login', static function (Request $request) use ($container): Response {
		$input = $request->json();
		$identifier = $input['identifier'] ?? null;
		$password = $input['password'] ?? null;
		if (!is_string($identifier) || !is_string($password) || $identifier === '' || $password === '') {
			throw \Sentinel\Core\HttpException::badRequest('Identifier and password are required.', 'invalid_login_request');
		}

		return Response::json(['data' => ['token' => $container->make('login_service')->login($identifier, $password)]]);
	});
    // Only this caller's effective access; the usual route checks remain authoritative.
    $router->get('/api/v1/auth/access', static function () use ($container, $router): Response {
        $context = $container->make('runtime_context');
        $authorization = $container->make('authorization_service');
        $allowed = []; $routes = []; $decisions = [];
        foreach ($router->securityMetadata() as $route) {
            $permission = $route['permission'];
            if (!is_string($permission) || $permission === '') continue;
            $decisions[$permission] ??= $authorization->isAllowed((string) $context->user(), $permission);
            if (!$decisions[$permission]) continue;
            $allowed[$permission] = true;
            if (in_array('GET', $route['methods'], true)) $routes[] = $route['path'];
        }
        return Response::json(['data'=>['permissions'=>array_keys($allowed), 'read_paths'=>array_values(array_unique($routes))]])->withHeader('Cache-Control', 'no-store');
    }, ['auth'=>true, 'tenant'=>true]);
	$router->get('/api/v1/auth/me', static function () use ($container): Response {
		$context = $container->make('runtime_context');

		return Response::json(['data' => ['user_id' => (string) $context->user()]]);
	}, ['auth' => true]);
	$router->get('/api/v1/auth/tenant', static function () use ($container): Response {
		$context = $container->make('runtime_context');

		return Response::json(['data' => ['company_id' => (string) $context->requireTenant()]]);
	}, ['tenant' => true]);
	$router->get('/api/v1/subscription', static fn (): Response => Response::json(['data' => $container->make('subscription_service')->current()]), ['auth' => true, 'tenant' => true, 'permission' => 'subscriptions.view']);
	$router->post('/api/v1/auth/password', static function (Request $request) use ($container): Response {
		$context = $container->make('runtime_context');
		$container->make('account_security_service')->changePassword((string) $context->user(), (string) ($request->json()['current_password'] ?? ''), (string) ($request->json()['new_password'] ?? ''));
		return Response::json(['data' => ['updated' => true]]);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'users.password_change']);
	$router->get('/api/v1/auth/permission-test', static function (): Response {
		return Response::json(['data' => ['authorized' => true]]);
	}, ['permission' => 'system.test']);
	$router->get('/api/v1/users', static function () use ($container): Response {
		return Response::json(['data' => $container->make('user_management_service')->listUsers()]);
	}, ['tenant' => true, 'permission' => 'users.view']);
	$router->get('/api/v1/users/{userId}', static function (Request $request, array $params) use ($container): Response {
		return Response::json(['data' => $container->make('user_management_service')->getUser((string) $params['userId'])]);
	}, ['tenant' => true, 'permission' => 'users.view']);
	$router->post('/api/v1/users', static function (Request $request) use ($container): Response {
		return Response::json(['data' => $container->make('user_management_service')->createUser($request->json())], 201);
	}, ['tenant' => true, 'permission' => 'users.create']);
	$router->patch('/api/v1/users/{userId}', static function (Request $request, array $params) use ($container): Response {
		return Response::json(['data' => $container->make('user_management_service')->updateUser((string) $params['userId'], $request->json())]);
	}, ['tenant' => true, 'permission' => 'users.update']);
	$router->get('/api/v1/roles', static function () use ($container): Response {
		return Response::json(['data' => $container->make('rbac_administration_service')->listRoles()]);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'roles.view']);
	$router->get('/api/v1/roles/{roleId}', static function (Request $request, array $params) use ($container): Response {
		return Response::json(['data' => $container->make('rbac_administration_service')->getRole((string) $params['roleId'])]);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'roles.view']);
	$router->post('/api/v1/roles', static function (Request $request) use ($container): Response {
		return Response::json(['data' => $container->make('rbac_administration_service')->createRole($request->json())], 201);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'roles.create']);
	$router->patch('/api/v1/roles/{roleId}', static function (Request $request, array $params) use ($container): Response {
		return Response::json(['data' => $container->make('rbac_administration_service')->updateRole((string) $params['roleId'], $request->json())]);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'roles.update']);
	$router->get('/api/v1/permissions', static function () use ($container): Response {
		return Response::json(['data' => $container->make('rbac_administration_service')->listPermissions()]);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'permissions.view']);
	$router->get('/api/v1/permissions/{permissionId}', static function (Request $request, array $params) use ($container): Response {
		return Response::json(['data' => $container->make('rbac_administration_service')->getPermission((string) $params['permissionId'])]);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'permissions.view']);
	$router->get('/api/v1/roles/{roleId}/permissions', static function (Request $request, array $params) use ($container): Response {
		return Response::json(['data' => $container->make('rbac_administration_service')->listRolePermissions((string) $params['roleId'])]);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'roles.view']);
	$router->post('/api/v1/roles/{roleId}/permissions', static function (Request $request, array $params) use ($container): Response {
		$container->make('rbac_administration_service')->grantPermission((string) $params['roleId'], (string) ($request->json()['permission_id'] ?? ''));
		return Response::json(['data' => ['assigned' => true]], 201);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'roles.assign_permissions']);
	$router->delete('/api/v1/roles/{roleId}/permissions/{permissionId}', static function (Request $request, array $params) use ($container): Response {
		$container->make('rbac_administration_service')->revokePermission((string) $params['roleId'], (string) $params['permissionId']);
		return Response::json(['data' => ['removed' => true]]);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'roles.assign_permissions']);
	$router->get('/api/v1/users/{userId}/roles', static function (Request $request, array $params) use ($container): Response {
		return Response::json(['data' => $container->make('rbac_administration_service')->listUserRoles((string) $params['userId'])]);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'user_roles.view']);
	$router->post('/api/v1/users/{userId}/roles', static function (Request $request, array $params) use ($container): Response {
		$container->make('rbac_administration_service')->assignRole((string) ($request->json()['role_id'] ?? ''), (string) $params['userId']);
		return Response::json(['data' => ['assigned' => true]], 201);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'user_roles.assign']);
	$router->delete('/api/v1/users/{userId}/roles/{roleId}', static function (Request $request, array $params) use ($container): Response {
		$container->make('rbac_administration_service')->removeRole((string) $params['roleId'], (string) $params['userId']);
		return Response::json(['data' => ['removed' => true]]);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'user_roles.assign']);
	$router->post('/api/v1/users/{userId}/unlock', static function (Request $request, array $params) use ($container): Response {
		$container->make('account_security_service')->unlockUser((string) $params['userId']);
		return Response::json(['data' => ['unlocked' => true]]);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'users.unlock']);
	$router->get('/api/v1/employees', static function () use ($container): Response {
		return Response::json(['data' => $container->make('employee_management_service')->listEmployees()]);
	}, ['tenant' => true, 'permission' => 'employees.view']);
	$router->get('/api/v1/employees/{employeeId}', static function (Request $request, array $params) use ($container): Response {
		return Response::json(['data' => $container->make('employee_management_service')->getEmployee((string) $params['employeeId'])]);
	}, ['tenant' => true, 'permission' => 'employees.view']);
	$router->post('/api/v1/employees', static function (Request $request) use ($container): Response {
		return Response::json(['data' => $container->make('employee_management_service')->createEmployee($request->json())], 201);
	}, ['tenant' => true, 'permission' => 'employees.create']);
	$router->patch('/api/v1/employees/{employeeId}', static function (Request $request, array $params) use ($container): Response {
		return Response::json(['data' => $container->make('employee_management_service')->updateEmployee((string) $params['employeeId'], $request->json())]);
	}, ['tenant' => true, 'permission' => 'employees.update']);
	$router->get('/api/v1/people', static function () use ($container): Response {
		return Response::json(['data' => $container->make('person_management_service')->listPeople()]);
	}, ['tenant' => true, 'permission' => 'people.view']);
	$router->get('/api/v1/people/{personId}', static function (Request $request, array $params) use ($container): Response {
		return Response::json(['data' => $container->make('person_management_service')->getPerson((string) $params['personId'])]);
	}, ['tenant' => true, 'permission' => 'people.view']);
	$router->post('/api/v1/people', static function (Request $request) use ($container): Response {
		return Response::json(['data' => $container->make('person_management_service')->createPerson($request->json())], 201);
	}, ['tenant' => true, 'permission' => 'people.create']);
	$router->patch('/api/v1/people/{personId}', static function (Request $request, array $params) use ($container): Response {
		return Response::json(['data' => $container->make('person_management_service')->updatePerson((string) $params['personId'], $request->json())]);
	}, ['tenant' => true, 'permission' => 'people.update']);
	$router->get('/api/v1/clients', static function () use ($container): Response {
		return Response::json(['data' => $container->make('client_management_service')->listClients()]);
	}, ['tenant' => true, 'permission' => 'clients.view']);
	$router->get('/api/v1/clients/{clientId}', static function (Request $request, array $params) use ($container): Response {
		return Response::json(['data' => $container->make('client_management_service')->getClient((string) $params['clientId'])]);
	}, ['tenant' => true, 'permission' => 'clients.view']);
	$router->post('/api/v1/clients', static function (Request $request) use ($container): Response {
		return Response::json(['data' => $container->make('client_management_service')->createClient($request->json())], 201);
	}, ['tenant' => true, 'permission' => 'clients.create']);
	$router->patch('/api/v1/clients/{clientId}', static function (Request $request, array $params) use ($container): Response {
		return Response::json(['data' => $container->make('client_management_service')->updateClient((string) $params['clientId'], $request->json())]);
	}, ['tenant' => true, 'permission' => 'clients.update']);
	$router->get('/api/v1/branches', static function () use ($container): Response {
		return Response::json(['data' => $container->make('branch_management_service')->listBranches()]);
	}, ['tenant' => true, 'permission' => 'branches.view']);
	$router->get('/api/v1/branches/{branchId}', static function (Request $request, array $params) use ($container): Response {
		return Response::json(['data' => $container->make('branch_management_service')->getBranch((string) $params['branchId'])]);
	}, ['tenant' => true, 'permission' => 'branches.view']);
	$router->post('/api/v1/branches', static function (Request $request) use ($container): Response {
		return Response::json(['data' => $container->make('branch_management_service')->createBranch($request->json())], 201);
	}, ['tenant' => true, 'permission' => 'branches.create']);
	$router->patch('/api/v1/branches/{branchId}', static function (Request $request, array $params) use ($container): Response {
		return Response::json(['data' => $container->make('branch_management_service')->updateBranch((string) $params['branchId'], $request->json())]);
	}, ['tenant' => true, 'permission' => 'branches.update']);
	$router->get('/api/v1/departments', static function () use ($container): Response {
		return Response::json(['data' => $container->make('department_management_service')->listDepartments()]);
	}, ['tenant' => true, 'permission' => 'departments.view']);
	$router->get('/api/v1/departments/{departmentId}', static function (Request $request, array $params) use ($container): Response {
		return Response::json(['data' => $container->make('department_management_service')->getDepartment((string) $params['departmentId'])]);
	}, ['tenant' => true, 'permission' => 'departments.view']);
	$router->post('/api/v1/departments', static function (Request $request) use ($container): Response {
		return Response::json(['data' => $container->make('department_management_service')->createDepartment($request->json())], 201);
	}, ['tenant' => true, 'permission' => 'departments.create']);
	$router->patch('/api/v1/departments/{departmentId}', static function (Request $request, array $params) use ($container): Response {
		return Response::json(['data' => $container->make('department_management_service')->updateDepartment((string) $params['departmentId'], $request->json())]);
	}, ['tenant' => true, 'permission' => 'departments.update']);
	$router->get('/api/v1/sites', static function () use ($container): Response {
		return Response::json(['data' => $container->make('site_management_service')->listSites()]);
	}, ['tenant' => true, 'permission' => 'sites.view']);
	$router->get('/api/v1/sites/{siteId}', static function (Request $request, array $params) use ($container): Response {
		return Response::json(['data' => $container->make('site_management_service')->getSite((string) $params['siteId'])]);
	}, ['tenant' => true, 'permission' => 'sites.view']);
	$router->post('/api/v1/sites', static function (Request $request) use ($container): Response {
		return Response::json(['data' => $container->make('site_management_service')->createSite($request->json())], 201);
	}, ['tenant' => true, 'permission' => 'sites.create']);
	$router->patch('/api/v1/sites/{siteId}', static function (Request $request, array $params) use ($container): Response {
		return Response::json(['data' => $container->make('site_management_service')->updateSite((string) $params['siteId'], $request->json())]);
	}, ['tenant' => true, 'permission' => 'sites.update']);
	$router->get('/api/v1/positions', static function () use ($container): Response {
		return Response::json(['data' => $container->make('position_management_service')->listPositions()]);
	}, ['tenant' => true, 'permission' => 'positions.view']);
	$router->get('/api/v1/positions/{positionId}', static function (Request $request, array $params) use ($container): Response {
		return Response::json(['data' => $container->make('position_management_service')->getPosition((string) $params['positionId'])]);
	}, ['tenant' => true, 'permission' => 'positions.view']);
	$router->post('/api/v1/positions', static function (Request $request) use ($container): Response {
		return Response::json(['data' => $container->make('position_management_service')->createPosition($request->json())], 201);
	}, ['tenant' => true, 'permission' => 'positions.create']);
	$router->patch('/api/v1/positions/{positionId}', static function (Request $request, array $params) use ($container): Response {
		return Response::json(['data' => $container->make('position_management_service')->updatePosition((string) $params['positionId'], $request->json())]);
	}, ['tenant' => true, 'permission' => 'positions.update']);
	$router->get('/api/v1/employee-assignments', static function () use ($container): Response {
		return Response::json(['data' => $container->make('employee_assignment_management_service')->listAssignments()]);
	}, ['tenant' => true, 'permission' => 'employee_assignments.view']);
	$router->get('/api/v1/employee-assignments/{assignmentId}', static function (Request $request, array $params) use ($container): Response {
		return Response::json(['data' => $container->make('employee_assignment_management_service')->getAssignment((string) $params['assignmentId'])]);
	}, ['tenant' => true, 'permission' => 'employee_assignments.view']);
	$router->post('/api/v1/employee-assignments', static function (Request $request) use ($container): Response {
		return Response::json(['data' => $container->make('employee_assignment_management_service')->createAssignment($request->json())], 201);
	}, ['tenant' => true, 'permission' => 'employee_assignments.create']);
	$router->patch('/api/v1/employee-assignments/{assignmentId}', static function (Request $request, array $params) use ($container): Response {
		return Response::json(['data' => $container->make('employee_assignment_management_service')->updateAssignment((string) $params['assignmentId'], $request->json())]);
	}, ['tenant' => true, 'permission' => 'employee_assignments.update']);
	$router->get('/api/v1/guards', static function () use ($container): Response {
		return Response::json(['data' => $container->make('guard_management_service')->listGuards()]);
	}, ['tenant' => true, 'permission' => 'guards.view']);
	$router->get('/api/v1/guards/{employeeId}', static function (Request $request, array $params) use ($container): Response {
		return Response::json(['data' => $container->make('guard_management_service')->getGuard((string) $params['employeeId'])]);
	}, ['tenant' => true, 'permission' => 'guards.view']);
	$router->post('/api/v1/guards', static function (Request $request) use ($container): Response {
		return Response::json(['data' => $container->make('guard_management_service')->registerGuard($request->json())], 201);
	}, ['tenant' => true, 'permission' => 'guards.create']);
	$router->patch('/api/v1/guards/{employeeId}', static function (Request $request, array $params) use ($container): Response {
		return Response::json(['data' => $container->make('guard_management_service')->updateGuardAssignment((string) $params['employeeId'], $request->json())]);
	}, ['tenant' => true, 'permission' => 'guards.update']);
	$router->get('/api/v1/guard-site-assignments', static function () use ($container): Response {
		return Response::json(['data' => $container->make('guard_site_assignment_management_service')->listAssignments()]);
	}, ['tenant' => true, 'permission' => 'guard_site_assignments.view']);
	$router->get('/api/v1/guard-site-assignments/{assignmentId}', static function (Request $request, array $params) use ($container): Response {
		return Response::json(['data' => $container->make('guard_site_assignment_management_service')->getAssignment((string) $params['assignmentId'])]);
	}, ['tenant' => true, 'permission' => 'guard_site_assignments.view']);
	$router->post('/api/v1/guard-site-assignments', static function (Request $request) use ($container): Response {
		return Response::json(['data' => $container->make('guard_site_assignment_management_service')->createAssignment($request->json())], 201);
	}, ['tenant' => true, 'permission' => 'guard_site_assignments.create']);
	$router->patch('/api/v1/guard-site-assignments/{assignmentId}', static function (Request $request, array $params) use ($container): Response {
		return Response::json(['data' => $container->make('guard_site_assignment_management_service')->updateAssignment((string) $params['assignmentId'], $request->json())]);
	}, ['tenant' => true, 'permission' => 'guard_site_assignments.update']);
	$router->get('/api/v1/shift-assignments', static function () use ($container): Response {
		return Response::json(['data' => $container->make('shift_assignment_management_service')->listShiftAssignments()]);
	}, ['tenant' => true, 'permission' => 'shift_assignments.view']);
	$router->get('/api/v1/shift-assignments/{assignmentId}', static function (Request $request, array $params) use ($container): Response {
		return Response::json(['data' => $container->make('shift_assignment_management_service')->getShiftAssignment((string) $params['assignmentId'])]);
	}, ['tenant' => true, 'permission' => 'shift_assignments.view']);
	$router->post('/api/v1/shift-assignments', static function (Request $request) use ($container): Response {
		return Response::json(['data' => $container->make('shift_assignment_management_service')->createShiftAssignment($request->json())], 201);
	}, ['tenant' => true, 'permission' => 'shift_assignments.create']);
	$router->patch('/api/v1/shift-assignments/{assignmentId}', static function (Request $request, array $params) use ($container): Response {
		return Response::json(['data' => $container->make('shift_assignment_management_service')->updateShiftAssignment((string) $params['assignmentId'], $request->json())]);
	}, ['tenant' => true, 'permission' => 'shift_assignments.update']);
	$router->get('/api/v1/attendance', static function () use ($container): Response {
		return Response::json(['data' => $container->make('attendance_management_service')->listAttendance()]);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'attendance.view']);
	$router->get('/api/v1/attendance/{attendanceId}', static function (Request $request, array $params) use ($container): Response {
		return Response::json(['data' => $container->make('attendance_management_service')->getAttendance((string) $params['attendanceId'])]);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'attendance.view']);
	$router->post('/api/v1/attendance', static function (Request $request) use ($container): Response {
		return Response::json(['data' => $container->make('attendance_management_service')->createAttendance($request->json())], 201);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'attendance.create']);
	$router->patch('/api/v1/attendance/{attendanceId}', static function (Request $request, array $params) use ($container): Response {
		return Response::json(['data' => $container->make('attendance_management_service')->updateAttendance((string) $params['attendanceId'], $request->json())]);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'attendance.update']);
	$router->get('/api/v1/patrol-routes', static function () use ($container): Response {
		return Response::json(['data' => $container->make('patrol_route_management_service')->listRoutes()]);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'patrol_routes.view']);
	$router->get('/api/v1/patrol-routes/{routeId}/checkpoints', static function (Request $request, array $params) use ($container): Response {
		return Response::json(['data' => $container->make('patrol_checkpoint_management_service')->listCheckpointsForRoute((string) $params['routeId'])]);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'patrol_checkpoints.view']);
	$router->get('/api/v1/patrol-routes/{routeId}', static function (Request $request, array $params) use ($container): Response {
		return Response::json(['data' => $container->make('patrol_route_management_service')->getRoute((string) $params['routeId'])]);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'patrol_routes.view']);
	$router->post('/api/v1/patrol-routes', static function (Request $request) use ($container): Response {
		return Response::json(['data' => $container->make('patrol_route_management_service')->createRoute($request->json())], 201);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'patrol_routes.create']);
	$router->patch('/api/v1/patrol-routes/{routeId}', static function (Request $request, array $params) use ($container): Response {
		return Response::json(['data' => $container->make('patrol_route_management_service')->updateRoute((string) $params['routeId'], $request->json())]);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'patrol_routes.update']);
	$router->post('/api/v1/patrol-routes/{routeId}/checkpoints', static function (Request $request, array $params) use ($container): Response {
		$input = $request->json();
		$input['patrol_route_id'] = (string) $params['routeId'];

		return Response::json(['data' => $container->make('patrol_checkpoint_management_service')->createCheckpoint($input)], 201);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'patrol_checkpoints.create']);
	$router->get('/api/v1/patrol-checkpoints/{checkpointId}', static function (Request $request, array $params) use ($container): Response {
		return Response::json(['data' => $container->make('patrol_checkpoint_management_service')->getCheckpoint((string) $params['checkpointId'])]);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'patrol_checkpoints.view']);
	$router->patch('/api/v1/patrol-checkpoints/{checkpointId}', static function (Request $request, array $params) use ($container): Response {
		return Response::json(['data' => $container->make('patrol_checkpoint_management_service')->updateCheckpoint((string) $params['checkpointId'], $request->json())]);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'patrol_checkpoints.update']);
	$router->get('/api/v1/patrol-sessions', static function () use ($container): Response {
		return Response::json(['data' => $container->make('patrol_session_management_service')->listSessions()]);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'patrol_sessions.view']);
	$router->get('/api/v1/patrol-sessions/{sessionId}/events', static function (Request $request, array $params) use ($container): Response {
		return Response::json(['data' => $container->make('patrol_checkpoint_event_management_service')->listEventsForSession((string) $params['sessionId'])]);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'patrol_checkpoint_events.view']);
	$router->get('/api/v1/patrol-sessions/{sessionId}', static function (Request $request, array $params) use ($container): Response {
		return Response::json(['data' => $container->make('patrol_session_management_service')->getSession((string) $params['sessionId'])]);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'patrol_sessions.view']);
	$router->post('/api/v1/patrol-sessions', static function (Request $request) use ($container): Response {
		return Response::json(['data' => $container->make('patrol_session_management_service')->startSession($request->json())], 201);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'patrol_sessions.create']);
	$router->patch('/api/v1/patrol-sessions/{sessionId}', static function (Request $request, array $params) use ($container): Response {
		return Response::json(['data' => $container->make('patrol_session_management_service')->updateSession((string) $params['sessionId'], $request->json())]);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'patrol_sessions.update']);
	$router->post('/api/v1/patrol-sessions/{sessionId}/events', static function (Request $request, array $params) use ($container): Response {
		return Response::json(['data' => $container->make('patrol_checkpoint_event_management_service')->recordEvent((string) $params['sessionId'], $request->json())], 201);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'patrol_checkpoint_events.create']);
	$router->get('/api/v1/locations', static function (Request $request) use ($container): Response {
		return Response::json(['data' => $container->make('location_management_service')->listLocations($request->queryParams())]);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'locations.view']);
	$router->post('/api/v1/locations', static function (Request $request) use ($container): Response {
		return Response::json(['data' => $container->make('location_management_service')->recordLocation($request->json())], 201);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'locations.create']);
	$router->get('/api/v1/employees/{employeeId}/locations', static function (Request $request, array $params) use ($container): Response {
		return Response::json(['data' => $container->make('location_management_service')->listEmployeeLocations((string) $params['employeeId'], $request->queryParams())]);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'locations.view']);
	$router->get('/api/v1/employees/{employeeId}/locations/latest', static function (Request $request, array $params) use ($container): Response {
		return Response::json(['data' => $container->make('location_management_service')->getLatestEmployeeLocation((string) $params['employeeId'])]);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'locations.view']);
	$router->get('/api/v1/geofences', static function () use ($container): Response {
		return Response::json(['data' => $container->make('geofence_management_service')->listGeofences()]);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'geofences.view']);
    $router->get('/api/v1/geofences/{geofenceId}/evaluate', static function (Request $request, array $params) use ($container): Response {
        $latitude = $request->query('latitude'); $longitude = $request->query('longitude');
        if (!is_scalar($latitude) || !is_scalar($longitude) || !is_numeric($latitude) || !is_numeric($longitude)) throw \Sentinel\Core\HttpException::unprocessable('Enter valid latitude and longitude.');
        return Response::json(['data'=>['inside'=>$container->make('geofence_management_service')->evaluate((string)$params['geofenceId'], (float)$latitude, (float)$longitude)]]);
    }, ['auth'=>true, 'tenant'=>true, 'permission'=>'geofences.view']);
	$router->get('/api/v1/geofences/{geofenceId}', static function (Request $request, array $params) use ($container): Response {
		return Response::json(['data' => $container->make('geofence_management_service')->getGeofence((string) $params['geofenceId'])]);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'geofences.view']);
	$router->post('/api/v1/geofences', static function (Request $request) use ($container): Response {
		return Response::json(['data' => $container->make('geofence_management_service')->createGeofence($request->json())], 201);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'geofences.create']);
	$router->patch('/api/v1/geofences/{geofenceId}', static function (Request $request, array $params) use ($container): Response {
		return Response::json(['data' => $container->make('geofence_management_service')->updateGeofence((string) $params['geofenceId'], $request->json())]);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'geofences.update']);
	$router->get('/api/v1/contracts', static function () use ($container): Response {
		return Response::json(['data' => $container->make('contract_management_service')->listContracts()]);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'contracts.view']);
	$router->get('/api/v1/contracts/{contractId}', static function (Request $request, array $params) use ($container): Response {
		return Response::json(['data' => $container->make('contract_management_service')->getContract((string) $params['contractId'])]);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'contracts.view']);
	$router->post('/api/v1/contracts', static function (Request $request) use ($container): Response {
		return Response::json(['data' => $container->make('contract_management_service')->createContract($request->json())], 201);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'contracts.create']);
	$router->patch('/api/v1/contracts/{contractId}', static function (Request $request, array $params) use ($container): Response {
		return Response::json(['data' => $container->make('contract_management_service')->updateContract((string) $params['contractId'], $request->json())]);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'contracts.update']);
	$router->get('/api/v1/posts', static function () use ($container): Response {
		return Response::json(['data' => $container->make('post_management_service')->listPosts()]);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'posts.view']);
	$router->get('/api/v1/posts/{postId}', static function (Request $request, array $params) use ($container): Response {
		return Response::json(['data' => $container->make('post_management_service')->getPost((string) $params['postId'])]);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'posts.view']);
	$router->post('/api/v1/posts', static function (Request $request) use ($container): Response {
		return Response::json(['data' => $container->make('post_management_service')->createPost($request->json())], 201);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'posts.create']);
	$router->patch('/api/v1/posts/{postId}', static function (Request $request, array $params) use ($container): Response {
		return Response::json(['data' => $container->make('post_management_service')->updatePost((string) $params['postId'], $request->json())]);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'posts.update']);
	$router->get('/api/v1/patrol-checkpoint-events/{eventId}', static function (Request $request, array $params) use ($container): Response {
		return Response::json(['data' => $container->make('patrol_checkpoint_event_management_service')->getEvent((string) $params['eventId'])]);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'patrol_checkpoint_events.view']);
	$router->get('/api/v1/dashboard/summary', static function () use ($container): Response {
		return Response::json(['data' => $container->make('dashboard_service')->summary()]);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'dashboard.view']);
	$router->get('/api/v1/incidents', static function (Request $request) use ($container): Response { return Response::json(['data' => $container->make('incident_management_service')->listIncidents($request->queryParams())]); }, ['auth' => true, 'tenant' => true, 'permission' => 'incidents.view']);
	$router->get('/api/v1/incidents/{incidentId}', static function (Request $request, array $params) use ($container): Response { return Response::json(['data' => $container->make('incident_management_service')->getIncident((string) $params['incidentId'])]); }, ['auth' => true, 'tenant' => true, 'permission' => 'incidents.view']);
	$router->post('/api/v1/incidents', static function (Request $request) use ($container): Response { return Response::json(['data' => $container->make('incident_management_service')->createIncident($request->json())], 201); }, ['auth' => true, 'tenant' => true, 'permission' => 'incidents.create']);
	$router->patch('/api/v1/incidents/{incidentId}', static function (Request $request, array $params) use ($container): Response { return Response::json(['data' => $container->make('incident_management_service')->updateIncident((string) $params['incidentId'], $request->json())]); }, ['auth' => true, 'tenant' => true, 'permission' => 'incidents.update']);
	$router->get('/api/v1/devices', static function (Request $request) use ($container): Response { return Response::json(['data' => $container->make('device_management_service')->listDevices($request->queryParams())]); }, ['auth' => true, 'tenant' => true, 'permission' => 'devices.view']);
	$router->get('/api/v1/devices/{deviceId}', static function (Request $request, array $params) use ($container): Response { return Response::json(['data' => $container->make('device_management_service')->getDevice((string) $params['deviceId'])]); }, ['auth' => true, 'tenant' => true, 'permission' => 'devices.view']);
	$router->post('/api/v1/devices', static function (Request $request) use ($container): Response { return Response::json(['data' => $container->make('device_management_service')->createDevice($request->json())], 201); }, ['auth' => true, 'tenant' => true, 'permission' => 'devices.create']);
	$router->patch('/api/v1/devices/{deviceId}', static function (Request $request, array $params) use ($container): Response { return Response::json(['data' => $container->make('device_management_service')->updateDevice((string) $params['deviceId'], $request->json())]); }, ['auth' => true, 'tenant' => true, 'permission' => 'devices.update']);
	$router->get('/api/v1/devices/{deviceId}/assignments', static function (Request $request, array $params) use ($container): Response { return Response::json(['data' => $container->make('device_assignment_management_service')->listDeviceAssignments((string) $params['deviceId'])]); }, ['auth' => true, 'tenant' => true, 'permission' => 'device_assignments.view']);
	$router->post('/api/v1/devices/{deviceId}/assignments', static function (Request $request, array $params) use ($container): Response { return Response::json(['data' => $container->make('device_assignment_management_service')->assignDevice((string) $params['deviceId'], $request->json())], 201); }, ['auth' => true, 'tenant' => true, 'permission' => 'device_assignments.manage']);
	$router->patch('/api/v1/device-assignments/{assignmentId}', static function (Request $request, array $params) use ($container): Response { return Response::json(['data' => $container->make('device_assignment_management_service')->updateAssignment((string) $params['assignmentId'], $request->json())]); }, ['auth' => true, 'tenant' => true, 'permission' => 'device_assignments.manage']);
	$router->post('/api/v1/device-assignments/{assignmentId}/end', static function (Request $request, array $params) use ($container): Response { return Response::json(['data' => $container->make('device_assignment_management_service')->endAssignment((string) $params['assignmentId'], $request->json())]); }, ['auth' => true, 'tenant' => true, 'permission' => 'device_assignments.manage']);
	$router->get('/api/v1/device-sessions', static function (Request $request) use ($container): Response { return Response::json(['data' => $container->make('device_session_management_service')->listSessions($request->queryParams())]); }, ['auth' => true, 'tenant' => true, 'permission' => 'device_sessions.view']);
	$router->get('/api/v1/device-sessions/{sessionId}', static function (Request $request, array $params) use ($container): Response { return Response::json(['data' => $container->make('device_session_management_service')->getSession((string) $params['sessionId'])]); }, ['auth' => true, 'tenant' => true, 'permission' => 'device_sessions.view']);
	$router->get('/api/v1/devices/{deviceId}/sessions', static function (Request $request, array $params) use ($container): Response { return Response::json(['data' => $container->make('device_session_management_service')->listDeviceSessions((string) $params['deviceId'])]); }, ['auth' => true, 'tenant' => true, 'permission' => 'device_sessions.view']);
	$router->post('/api/v1/device-sessions', static function (Request $request) use ($container): Response { return Response::json(['data' => $container->make('device_session_management_service')->startSession($request->json())], 201); }, ['auth' => true, 'tenant' => true, 'permission' => 'device_sessions.manage']);
	$router->post('/api/v1/device-sessions/{sessionId}/end', static function (Request $request, array $params) use ($container): Response { return Response::json(['data' => $container->make('device_session_management_service')->endSession((string) $params['sessionId'], $request->json())]); }, ['auth' => true, 'tenant' => true, 'permission' => 'device_sessions.manage']);
	foreach (['attendance', 'shifts', 'guard-deployments', 'patrol-sessions', 'patrol-checkpoints', 'locations', 'geofences', 'contracts', 'posts', 'audit'] as $report) {
		$router->get('/api/v1/reports/' . $report, static function (Request $request) use ($container, $report): Response {
			return Response::json(['data' => $container->make('reporting_service')->report($report, $request->queryParams())]);
		}, ['auth' => true, 'tenant' => true, 'permission' => 'reports.view']);
	}
	$router->get('/api/v1/reports/{report}/csv', static function (Request $request, array $params) use ($container): Response {
		$csv = $container->make('reporting_service')->csv((string) $params['report'], $request->queryParams());
		$filename = preg_replace('/[^a-z0-9-]/', '', strtolower((string) $params['report'])) . '-report.csv';
		return Response::raw($csv, 200, ['Content-Type' => 'text/csv; charset=utf-8', 'Content-Disposition' => 'attachment; filename="' . $filename . '"']);
	}, ['auth' => true, 'tenant' => true, 'permission' => 'reports.view']);
    $router->get('/api/v1/occurrence-entries', static function (Request $request) use ($container): Response { return Response::json(['data' => $container->make('occurrence_book_service')->listEntries($request->queryParams())]); }, ['auth' => true, 'tenant' => true, 'permission' => 'occurrence_book.view']);
    $router->get('/api/v1/occurrence-entries/{entryId}', static function (Request $request, array $params) use ($container): Response { return Response::json(['data' => $container->make('occurrence_book_service')->getEntry((string) $params['entryId'])]); }, ['auth' => true, 'tenant' => true, 'permission' => 'occurrence_book.view']);
    $router->get('/api/v1/occurrence-entries/{entryId}/revisions', static function (Request $request, array $params) use ($container): Response { return Response::json(['data' => $container->make('occurrence_book_service')->revisions((string) $params['entryId'])]); }, ['auth' => true, 'tenant' => true, 'permission' => 'occurrence_book.view']);
    $router->post('/api/v1/occurrence-entries', static function (Request $request) use ($container): Response { return Response::json(['data' => $container->make('occurrence_book_service')->createEntry($request->json())], 201); }, ['auth' => true, 'tenant' => true, 'permission' => 'occurrence_book.create']);
    $router->post('/api/v1/occurrence-entries/{entryId}/revisions', static function (Request $request, array $params) use ($container): Response { return Response::json(['data' => $container->make('occurrence_book_service')->reviseEntry((string) $params['entryId'], $request->json())]); }, ['auth' => true, 'tenant' => true, 'permission' => 'occurrence_book.revise']);
    foreach (['visitors' => 'visitors', 'vehicles' => 'vehicles', 'visitor-visits' => 'visits', 'access-events' => 'access'] as $path => $domain) {
        $permission = str_replace('-', '_', $path);
        $router->get('/api/v1/' . $path, static function (Request $request) use ($container, $domain): Response { return Response::json(['data' => $container->make('access_management_service')->listRecords($domain, $request->queryParams())]); }, ['auth' => true, 'tenant' => true, 'permission' => $permission . '.view']);
        $router->get('/api/v1/' . $path . '/{recordId}', static function (Request $request, array $params) use ($container, $domain): Response { return Response::json(['data' => $container->make('access_management_service')->getRecord($domain, (string) $params['recordId'])]); }, ['auth' => true, 'tenant' => true, 'permission' => $permission . '.view']);
        $router->post('/api/v1/' . $path, static function (Request $request) use ($container, $domain): Response { return Response::json(['data' => $container->make('access_management_service')->createRecord($domain, $request->json())], 201); }, ['auth' => true, 'tenant' => true, 'permission' => $permission . '.create']);
        if (in_array($domain, ['visitors', 'vehicles'], true)) {
            $router->patch('/api/v1/' . $path . '/{recordId}', static function (Request $request, array $params) use ($container, $domain): Response { return Response::json(['data' => $container->make('access_management_service')->updateRecord($domain, (string) $params['recordId'], $request->json())]); }, ['auth' => true, 'tenant' => true, 'permission' => $permission . '.update']);
        }
    }
    $router->post('/api/v1/visitor-visits/{recordId}/status', static function (Request $request, array $params) use ($container): Response { return Response::json(['data' => $container->make('access_management_service')->transitionVisit((string) $params['recordId'], $request->json())]); }, ['auth' => true, 'tenant' => true, 'permission' => 'visitor_visits.manage']);
    $router->get('/api/v1/inspections', static function (Request $request) use ($container): Response { return Response::json(['data'=>$container->make('inspection_management_service')->listInspections($request->queryParams())]); }, ['auth'=>true,'tenant'=>true,'permission'=>'inspections.view']);
    $router->get('/api/v1/inspections/{id}', static function (Request $request,array $params) use ($container): Response { return Response::json(['data'=>$container->make('inspection_management_service')->getInspection((string)$params['id'])]); }, ['auth'=>true,'tenant'=>true,'permission'=>'inspections.view']);
    $router->post('/api/v1/inspections', static function (Request $request) use ($container): Response { return Response::json(['data'=>$container->make('inspection_management_service')->createInspection($request->json())],201); }, ['auth'=>true,'tenant'=>true,'permission'=>'inspections.create']);
    $router->patch('/api/v1/inspections/{id}', static function (Request $request,array $params) use ($container): Response { return Response::json(['data'=>$container->make('inspection_management_service')->updateInspection((string)$params['id'],$request->json())]); }, ['auth'=>true,'tenant'=>true,'permission'=>'inspections.update']);
    $router->get('/api/v1/inspections/{id}/items', static function (Request $request,array $params) use ($container): Response { return Response::json(['data'=>$container->make('inspection_management_service')->items((string)$params['id'])]); }, ['auth'=>true,'tenant'=>true,'permission'=>'inspections.view']);
    $router->post('/api/v1/inspections/{id}/items', static function (Request $request,array $params) use ($container): Response { return Response::json(['data'=>$container->make('inspection_management_service')->saveItem((string)$params['id'],null,$request->json())],201); }, ['auth'=>true,'tenant'=>true,'permission'=>'inspections.update']);
    $router->patch('/api/v1/inspections/{id}/items/{itemId}', static function (Request $request,array $params) use ($container): Response { return Response::json(['data'=>$container->make('inspection_management_service')->saveItem((string)$params['id'],(string)$params['itemId'],$request->json())]); }, ['auth'=>true,'tenant'=>true,'permission'=>'inspections.update']);
    $router->post('/api/v1/inspections/{id}/status', static function (Request $request,array $params) use ($container): Response { return Response::json(['data'=>$container->make('inspection_management_service')->transition((string)$params['id'],$request->json())]); }, ['auth'=>true,'tenant'=>true,'permission'=>'inspections.update']);
    $router->post('/api/v1/inspections/{id}/review', static function (Request $request,array $params) use ($container): Response { return Response::json(['data'=>$container->make('inspection_management_service')->transition((string)$params['id'],$request->json(),true)]); }, ['auth'=>true,'tenant'=>true,'permission'=>'inspections.review']);
    $router->get('/api/v1/alerts', static function (Request $request) use ($container): Response { return Response::json(['data'=>$container->make('alert_notification_service')->alerts($request->queryParams())]); }, ['auth'=>true,'tenant'=>true,'permission'=>'alerts.view']);
    $router->get('/api/v1/alerts/{id}', static function (Request $request,array $params) use ($container): Response { return Response::json(['data'=>$container->make('alert_notification_service')->getAlert((string)$params['id'])]); }, ['auth'=>true,'tenant'=>true,'permission'=>'alerts.view']);
    $router->post('/api/v1/alerts', static function (Request $request) use ($container): Response { return Response::json(['data'=>$container->make('alert_notification_service')->createAlert($request->json())],201); }, ['auth'=>true,'tenant'=>true,'permission'=>'alerts.create']);
    $router->post('/api/v1/alerts/{id}/status', static function (Request $request,array $params) use ($container): Response { return Response::json(['data'=>$container->make('alert_notification_service')->transitionAlert((string)$params['id'],$request->json())]); }, ['auth'=>true,'tenant'=>true,'permission'=>'alerts.manage']);
    $router->get('/api/v1/notifications', static function (Request $request) use ($container): Response { return Response::json(['data'=>$container->make('alert_notification_service')->inbox($request->queryParams())]); }, ['auth'=>true,'tenant'=>true,'permission'=>'notifications.view']);
    $router->get('/api/v1/notifications/{id}', static function (Request $request,array $params) use ($container): Response { return Response::json(['data'=>$container->make('alert_notification_service')->getNotification((string)$params['id'])]); }, ['auth'=>true,'tenant'=>true,'permission'=>'notifications.view']);
    $router->post('/api/v1/notifications', static function (Request $request) use ($container): Response { return Response::json(['data'=>$container->make('alert_notification_service')->createNotification($request->json())],201); }, ['auth'=>true,'tenant'=>true,'permission'=>'notifications.create']);
    $router->post('/api/v1/notifications/{id}/read', static function (Request $request,array $params) use ($container): Response { return Response::json(['data'=>$container->make('alert_notification_service')->markRead((string)$params['id'],$request->json())]); }, ['auth'=>true,'tenant'=>true,'permission'=>'notifications.view']);
    $router->get('/api/v1/notifications/{id}/deliveries', static function (Request $request,array $params) use ($container): Response { return Response::json(['data'=>$container->make('alert_notification_service')->deliveries((string)$params['id'])]); }, ['auth'=>true,'tenant'=>true,'permission'=>'notifications.view']);
    $router->get('/api/v1/reports/operational/{name}', static function (Request $request,array $params) use ($container): Response { return Response::json(['data'=>$container->make('operational_reporting_service')->report((string)$params['name'],$request->queryParams())]); }, ['auth'=>true,'tenant'=>true,'permission'=>'reports.view']);
    $router->get('/api/v1/reports/operational/{name}/csv', static function (Request $request,array $params) use ($container): Response { return Response::raw($container->make('operational_reporting_service')->csv((string)$params['name'],$request->queryParams()),200,['Content-Type'=>'text/csv; charset=utf-8','Content-Disposition'=>'attachment; filename="operational-report.csv"']); }, ['auth'=>true,'tenant'=>true,'permission'=>'reports.view']);
    $router->get('/api/v1/dashboard/operations', static function () use ($container): Response { return Response::json(['data'=>$container->make('operational_reporting_service')->summary()]); }, ['auth'=>true,'tenant'=>true,'permission'=>'dashboard.view']);
    $router->get('/api/v1/authority-levels', static function () use ($container): Response { return Response::json(['data'=>$container->make('employee_assignment_management_service')->authorityLevels()]); }, ['auth'=>true,'tenant'=>true,'permission'=>'employee_assignments.view']);
    $router->get('/api/v1/patrol-schedules', static function (Request $request) use ($container): Response { return Response::json(['data'=>$container->make('patrol_schedule_service')->list($request->queryParams())]); }, ['auth'=>true,'tenant'=>true,'permission'=>'patrol_sessions.view']);
    $router->post('/api/v1/patrol-schedules', static function (Request $request) use ($container): Response { return Response::json(['data'=>$container->make('patrol_schedule_service')->create($request->json())],201); }, ['auth'=>true,'tenant'=>true,'permission'=>'patrol_sessions.create']);
    $router->post('/api/v1/patrol-schedules/{id}/cancel', static function (Request $request,array $params) use ($container): Response { return Response::json(['data'=>$container->make('patrol_schedule_service')->cancel((string)$params['id'])]); }, ['auth'=>true,'tenant'=>true,'permission'=>'patrol_sessions.update']);
    $router->get('/api/v1/guard/patrols/{id}', static function (Request $request,array $params) use ($container): Response { return Response::json(['data'=>$container->make('guard_workflow_service')->patrolDetails((string)$params['id'])]); }, ['auth'=>true,'tenant'=>true,'permission'=>'guard.self']);
    $router->get('/api/v1/guard/patrol-schedules', static function (Request $request) use ($container): Response { return Response::json(['data'=>$container->make('guard_workflow_service')->patrolSchedules($request->queryParams())]); }, ['auth'=>true,'tenant'=>true,'permission'=>'guard.self']);
    $router->get('/api/v1/guard/workspace', static function () use ($container): Response { return Response::json(['data'=>$container->make('guard_workflow_service')->workspace()]); }, ['auth'=>true,'tenant'=>true,'permission'=>'guard.self']);
    $router->post('/api/v1/guard/commands', static function (Request $request) use ($container): Response { return Response::json(['data'=>$container->make('guard_workflow_service')->command($request->json())]); }, ['auth'=>true,'tenant'=>true,'permission'=>'guard.self']);
    $router->get('/api/v1/supervisor/attention', static function (Request $request) use ($container): Response { return Response::json(['data'=>$container->make('supervisor_attention_service')->attention($request->queryParams())]); }, ['auth'=>true,'tenant'=>true,'permission'=>'supervisor.view']);
    $router->post('/api/v1/help/assistant', static function (Request $request) use ($container): Response {
        return Response::json(['data'=>$container->make('ai_help_service')->answer($request->json(), 'manager')]);
    }, ['auth'=>true,'tenant'=>true,'permission'=>'dashboard.view']);
    $router->post('/api/v1/guard/help', static function (Request $request) use ($container): Response {
        return Response::json(['data'=>$container->make('ai_help_service')->answer($request->json(), 'guard')]);
    }, ['auth'=>true,'tenant'=>true,'permission'=>'guard.self']);
};

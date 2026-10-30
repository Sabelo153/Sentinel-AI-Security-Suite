<?php

declare(strict_types=1);

namespace Sentinel\Core;

/**
 * Composition root: wires core services and the explicit HTTP route table.
 * Authentication, middleware, and business logic remain later phases.
 */
final class Application
{
    private const SERVICE_CONFIG = 'config';
    private const SERVICE_LOGGER = 'logger';
    private const SERVICE_DATABASE = 'database';
    private const SERVICE_RUNTIME_CONTEXT = 'runtime_context';
    private const SERVICE_ROUTER = 'router';
    private const SERVICE_MIDDLEWARE_PIPELINE = 'middleware_pipeline';
    private const SERVICE_JWT = 'jwt';
    private const SERVICE_AUTHENTICATION_MIDDLEWARE = 'authentication_middleware';
    private const SERVICE_PERMISSION_REPOSITORY = 'permission_repository';
    private const SERVICE_ROLE_REPOSITORY = 'role_repository';
    private const SERVICE_AUTHORIZATION_SERVICE = 'authorization_service';
    private const SERVICE_AUTHORIZATION_MIDDLEWARE = 'authorization_middleware';
    private const SERVICE_TENANT_CONTEXT_SERVICE = 'tenant_context_service';
    private const SERVICE_TENANT_MIDDLEWARE = 'tenant_middleware';
    private const SERVICE_USER_MANAGEMENT = 'user_management_service';
    private const SERVICE_RBAC_ADMINISTRATION_REPOSITORY = 'rbac_administration_repository';
    private const SERVICE_RBAC_ADMINISTRATION = 'rbac_administration_service';
    private const SERVICE_REPORTING_REPOSITORY = 'reporting_repository';
    private const SERVICE_CSV_EXPORT = 'csv_export_service';
    private const SERVICE_REPORTING = 'reporting_service';
    private const SERVICE_DASHBOARD_REPOSITORY = 'dashboard_repository';
    private const SERVICE_DASHBOARD = 'dashboard_service';
    private const SERVICE_DEVICE_REPOSITORY = 'device_repository';
    private const SERVICE_DEVICE_MANAGEMENT = 'device_management_service';
    private const SERVICE_DEVICE_ASSIGNMENT_REPOSITORY = 'device_assignment_repository';
    private const SERVICE_DEVICE_ASSIGNMENT_MANAGEMENT = 'device_assignment_management_service';
    private const SERVICE_DEVICE_SESSION_REPOSITORY = 'device_session_repository';
    private const SERVICE_DEVICE_SESSION_MANAGEMENT = 'device_session_management_service';
    private const SERVICE_INCIDENT_REPOSITORY = 'incident_repository';
    private const SERVICE_INCIDENT_MANAGEMENT = 'incident_management_service';
    private const SERVICE_EMPLOYEE_REPOSITORY = 'employee_repository';
    private const SERVICE_EMPLOYEE_MANAGEMENT = 'employee_management_service';
    private const SERVICE_PERSON_REPOSITORY = 'person_repository';
    private const SERVICE_PERSON_MANAGEMENT = 'person_management_service';
    private const SERVICE_CLIENT_REPOSITORY = 'client_repository';
    private const SERVICE_CLIENT_MANAGEMENT = 'client_management_service';
    private const SERVICE_BRANCH_REPOSITORY = 'branch_repository';
    private const SERVICE_BRANCH_MANAGEMENT = 'branch_management_service';
    private const SERVICE_DEPARTMENT_REPOSITORY = 'department_repository';
    private const SERVICE_DEPARTMENT_MANAGEMENT = 'department_management_service';
    private const SERVICE_SITE_REPOSITORY = 'site_repository';
    private const SERVICE_SITE_MANAGEMENT = 'site_management_service';
    private const SERVICE_POSITION_REPOSITORY = 'position_repository';
    private const SERVICE_POSITION_MANAGEMENT = 'position_management_service';
    private const SERVICE_ASSIGNMENT_REPOSITORY = 'employee_assignment_repository';
    private const SERVICE_ASSIGNMENT_MANAGEMENT = 'employee_assignment_management_service';
    private const SERVICE_GUARD_REPOSITORY = 'guard_repository';
    private const SERVICE_GUARD_MANAGEMENT = 'guard_management_service';
    private const SERVICE_GUARD_SITE_ASSIGNMENT_REPOSITORY = 'guard_site_assignment_repository';
    private const SERVICE_GUARD_SITE_ASSIGNMENT_MANAGEMENT = 'guard_site_assignment_management_service';
    private const SERVICE_SHIFT_ASSIGNMENT_REPOSITORY = 'shift_assignment_repository';
    private const SERVICE_SHIFT_ASSIGNMENT_MANAGEMENT = 'shift_assignment_management_service';
    private const SERVICE_ATTENDANCE_REPOSITORY = 'attendance_repository';
    private const SERVICE_ATTENDANCE_MANAGEMENT = 'attendance_management_service';
    private const SERVICE_PATROL_ROUTE_REPOSITORY = 'patrol_route_repository';
    private const SERVICE_PATROL_ROUTE_MANAGEMENT = 'patrol_route_management_service';
    private const SERVICE_PATROL_CHECKPOINT_REPOSITORY = 'patrol_checkpoint_repository';
    private const SERVICE_PATROL_CHECKPOINT_MANAGEMENT = 'patrol_checkpoint_management_service';
    private const SERVICE_PATROL_SESSION_REPOSITORY = 'patrol_session_repository';
    private const SERVICE_PATROL_SESSION_MANAGEMENT = 'patrol_session_management_service';
    private const SERVICE_PATROL_CHECKPOINT_EVENT_REPOSITORY = 'patrol_checkpoint_event_repository';
    private const SERVICE_PATROL_CHECKPOINT_EVENT_MANAGEMENT = 'patrol_checkpoint_event_management_service';
    private const SERVICE_LOCATION_REPOSITORY = 'location_repository';
    private const SERVICE_LOCATION_MANAGEMENT = 'location_management_service';
    private const SERVICE_GEOFENCE_REPOSITORY = 'geofence_repository';
    private const SERVICE_GEOFENCE_MANAGEMENT = 'geofence_management_service';
    private const SERVICE_CONTRACT_REPOSITORY = 'contract_repository';
    private const SERVICE_CONTRACT_MANAGEMENT = 'contract_management_service';
    private const SERVICE_POST_REPOSITORY = 'post_repository';
    private const SERVICE_POST_MANAGEMENT = 'post_management_service';
    private const SERVICE_USER_REPOSITORY = 'user_repository';
    private const SERVICE_PASSWORD_AUTHENTICATOR = 'password_authenticator';
    private const SERVICE_LOGIN = 'login_service';
    private const SERVICE_ACCOUNT_SECURITY = 'account_security_service';
    private const SERVICE_AUDIT_REPOSITORY = 'audit_repository';
    private const SERVICE_AUDIT = 'audit_service';

    public function __construct(
        private readonly Container $container,
        private readonly string $basePath,
        private bool $routesLoaded = false,
    ) {
    }

    /**
     * $basePath defaults to the project root computed from this file's own
     * location, so bootstrapping works regardless of the caller's CWD.
     */
    public static function create(?string $basePath = null): self
    {
        return new self(new Container(), $basePath ?? dirname(__DIR__, 2));
    }

    /**
     * Prepare the runtime: load configuration and register core services.
     * Deliberately does not connect to the database — Database's own
     * connect() stays lazy until something actually issues a query.
     */
    public function bootstrap(): self
    {
        $config = Config::fromDirectory($this->basePath . '/config');
        $this->container->instance(self::SERVICE_CONFIG, $config);

        $this->container->singleton(
            self::SERVICE_LOGGER,
            static fn (Container $c): Logger => new Logger($c->make(self::SERVICE_CONFIG)),
        );

        $this->container->singleton(
            self::SERVICE_DATABASE,
            static fn (Container $c): Database => new Database($c->make(self::SERVICE_CONFIG)),
        );

        $this->container->singleton(
            'support_diagnostics_service',
            static fn (Container $c): \Sentinel\Services\SupportDiagnosticsService => new \Sentinel\Services\SupportDiagnosticsService(
                $c->make(self::SERVICE_CONFIG),
                $c->make(self::SERVICE_DATABASE),
            ),
        );

        $this->container->singleton('support_workspace_service', static fn (Container $c): \Sentinel\Services\SupportWorkspaceService => new \Sentinel\Services\SupportWorkspaceService(
            $c->make(self::SERVICE_DATABASE), $c->make(self::SERVICE_RUNTIME_CONTEXT), $c->make(self::SERVICE_AUDIT),
        ));
        $this->container->singleton('managed_android_service', static fn (Container $c): \Sentinel\Services\ManagedAndroidService => new \Sentinel\Services\ManagedAndroidService($c->make(self::SERVICE_DATABASE),$c->make(self::SERVICE_RUNTIME_CONTEXT),$c->make(self::SERVICE_AUDIT)));
        $this->container->singleton('session_security_service', static fn (Container $c): \Sentinel\Services\SessionSecurityService => new \Sentinel\Services\SessionSecurityService($c->make(self::SERVICE_DATABASE),$c->make(self::SERVICE_RUNTIME_CONTEXT),$c->make(self::SERVICE_CONFIG),$c->make(self::SERVICE_AUDIT)));
        $this->container->singleton('field_operations_service', static fn (Container $c): \Sentinel\Services\FieldOperationsService => new \Sentinel\Services\FieldOperationsService($c->make(self::SERVICE_DATABASE),$c->make(self::SERVICE_RUNTIME_CONTEXT),$c->make(self::SERVICE_AUDIT),$c->make('supervisor_attention_service'),$c->make('alert_notification_service')));

        $this->container->singleton(
            self::SERVICE_RUNTIME_CONTEXT,
            static fn (Container $c): RuntimeContext => new RuntimeContext(
                environment: (string) $c->make(self::SERVICE_CONFIG)->get('app.env', 'production'),
            ),
        );

        if (!$this->container->has(self::SERVICE_ROUTER)) {
            $this->container->singleton(
                self::SERVICE_ROUTER,
                static fn (): Router => new Router(),
            );
        }

        if (!$this->container->has(self::SERVICE_MIDDLEWARE_PIPELINE)) {
            $this->container->singleton(
                self::SERVICE_MIDDLEWARE_PIPELINE,
                static fn (): MiddlewarePipeline => new MiddlewarePipeline(),
            );
        }

        if (!$this->container->has(self::SERVICE_JWT)) {
            $this->container->singleton(
                self::SERVICE_JWT,
                static fn (Container $c): \Sentinel\Security\JwtService => new \Sentinel\Security\JwtService($c->make(self::SERVICE_CONFIG), $c->make('session_security_service')),
            );
        }

        if (!$this->container->has(self::SERVICE_AUTHENTICATION_MIDDLEWARE)) {
            $this->container->singleton(
                self::SERVICE_AUTHENTICATION_MIDDLEWARE,
                static fn (Container $c): \Sentinel\Security\AuthenticationMiddleware => new \Sentinel\Security\AuthenticationMiddleware(
                    $c->make(self::SERVICE_JWT),
                    $c->make(self::SERVICE_RUNTIME_CONTEXT),
                ),
            );
        }

        if (!$this->container->has(self::SERVICE_PERMISSION_REPOSITORY)) {
            $this->container->singleton(
                self::SERVICE_PERMISSION_REPOSITORY,
                static fn (Container $c): \Sentinel\Repositories\PermissionRepository => new \Sentinel\Repositories\PermissionRepository($c->make(self::SERVICE_DATABASE), $c->make(self::SERVICE_RUNTIME_CONTEXT)),
            );
        }

        if (!$this->container->has(self::SERVICE_ROLE_REPOSITORY)) {
            $this->container->singleton(
                self::SERVICE_ROLE_REPOSITORY,
                static fn (Container $c): \Sentinel\Repositories\RoleRepository => new \Sentinel\Repositories\RoleRepository($c->make(self::SERVICE_DATABASE)),
            );
        }

        if (!$this->container->has(self::SERVICE_AUTHORIZATION_SERVICE)) {
            if (!$this->container->has('subscription_service')) {
                $this->container->singleton('subscription_service', static fn (Container $c): \Sentinel\Services\SubscriptionService => new \Sentinel\Services\SubscriptionService($c->make(self::SERVICE_DATABASE), $c->make(self::SERVICE_RUNTIME_CONTEXT)));
            }
            $this->container->singleton(
                self::SERVICE_AUTHORIZATION_SERVICE,
                static fn (Container $c): \Sentinel\Security\AuthorizationService => new \Sentinel\Security\AuthorizationService(
                    $c->make(self::SERVICE_PERMISSION_REPOSITORY),
                    $c->make(self::SERVICE_RUNTIME_CONTEXT),
                    $c->make('subscription_service'),
                ),
            );
        }

        if (!$this->container->has(self::SERVICE_AUTHORIZATION_MIDDLEWARE)) {
            $this->container->singleton(
                self::SERVICE_AUTHORIZATION_MIDDLEWARE,
                static fn (Container $c): \Sentinel\Security\AuthorizationMiddleware => new \Sentinel\Security\AuthorizationMiddleware(
                    $c->make(self::SERVICE_AUTHORIZATION_SERVICE),
                    $c->make(self::SERVICE_RUNTIME_CONTEXT),
                ),
            );
        }

        if (!$this->container->has(self::SERVICE_TENANT_CONTEXT_SERVICE)) {
            $this->container->singleton(
                self::SERVICE_TENANT_CONTEXT_SERVICE,
                static fn (Container $c): \Sentinel\Security\TenantContextService => new \Sentinel\Security\TenantContextService(
                    $c->make(self::SERVICE_USER_REPOSITORY),
                    $c->make(self::SERVICE_RUNTIME_CONTEXT),
                ),
            );
        }

        if (!$this->container->has(self::SERVICE_TENANT_MIDDLEWARE)) {
            $this->container->singleton(
                self::SERVICE_TENANT_MIDDLEWARE,
                static fn (Container $c): \Sentinel\Security\TenantMiddleware => new \Sentinel\Security\TenantMiddleware(
                    $c->make(self::SERVICE_TENANT_CONTEXT_SERVICE),
                ),
            );
        }

        if (!$this->container->has(self::SERVICE_USER_REPOSITORY)) {
            $this->container->singleton(
                self::SERVICE_USER_REPOSITORY,
                static fn (Container $c): \Sentinel\Repositories\UserRepository => new \Sentinel\Repositories\UserRepository(
                    $c->make(self::SERVICE_DATABASE),
                    $c->make(self::SERVICE_RUNTIME_CONTEXT),
                ),
            );
        }

        if (!$this->container->has(self::SERVICE_USER_MANAGEMENT)) {
            $this->container->singleton(
                self::SERVICE_USER_MANAGEMENT,
                static fn (Container $c): \Sentinel\Services\UserManagementService => new \Sentinel\Services\UserManagementService(
                    $c->make(self::SERVICE_USER_REPOSITORY),
                    $c->make('subscription_service'),
                ),
            );
        }

        if (!$this->container->has(self::SERVICE_RBAC_ADMINISTRATION_REPOSITORY)) {
            $this->container->singleton(
                self::SERVICE_RBAC_ADMINISTRATION_REPOSITORY,
                static fn (Container $c): \Sentinel\Repositories\RbacAdministrationRepository => new \Sentinel\Repositories\RbacAdministrationRepository($c->make(self::SERVICE_DATABASE), $c->make(self::SERVICE_RUNTIME_CONTEXT)),
            );
        }

        if (!$this->container->has(self::SERVICE_RBAC_ADMINISTRATION)) {
            $this->container->singleton(
                self::SERVICE_RBAC_ADMINISTRATION,
                static fn (Container $c): \Sentinel\Services\RbacAdministrationService => new \Sentinel\Services\RbacAdministrationService($c->make(self::SERVICE_RBAC_ADMINISTRATION_REPOSITORY), $c->make(self::SERVICE_RUNTIME_CONTEXT), $c->make(self::SERVICE_AUDIT)),
            );
        }

        if (!$this->container->has(self::SERVICE_REPORTING_REPOSITORY)) {
            $this->container->singleton(self::SERVICE_REPORTING_REPOSITORY, static fn (Container $c): \Sentinel\Repositories\ReportingRepository => new \Sentinel\Repositories\ReportingRepository($c->make(self::SERVICE_DATABASE), $c->make(self::SERVICE_RUNTIME_CONTEXT)));
        }
        if (!$this->container->has(self::SERVICE_CSV_EXPORT)) {
            $this->container->singleton(self::SERVICE_CSV_EXPORT, static fn (): \Sentinel\Services\CsvExportService => new \Sentinel\Services\CsvExportService());
        }
        if (!$this->container->has(self::SERVICE_REPORTING)) {
            $this->container->singleton(self::SERVICE_REPORTING, static fn (Container $c): \Sentinel\Services\ReportingService => new \Sentinel\Services\ReportingService($c->make(self::SERVICE_REPORTING_REPOSITORY), $c->make(self::SERVICE_CSV_EXPORT)));
        }
        if (!$this->container->has(self::SERVICE_DASHBOARD_REPOSITORY)) {
            $this->container->singleton(self::SERVICE_DASHBOARD_REPOSITORY, static fn (Container $c): \Sentinel\Repositories\DashboardRepository => new \Sentinel\Repositories\DashboardRepository($c->make(self::SERVICE_DATABASE), $c->make(self::SERVICE_RUNTIME_CONTEXT)));
        }
        if (!$this->container->has(self::SERVICE_DASHBOARD)) {
            $this->container->singleton(self::SERVICE_DASHBOARD, static fn (Container $c): \Sentinel\Services\DashboardService => new \Sentinel\Services\DashboardService($c->make(self::SERVICE_DASHBOARD_REPOSITORY)));
        }
        if (!$this->container->has(self::SERVICE_DEVICE_REPOSITORY)) {
            $this->container->singleton(self::SERVICE_DEVICE_REPOSITORY, static fn (Container $c): \Sentinel\Repositories\DeviceRepository => new \Sentinel\Repositories\DeviceRepository($c->make(self::SERVICE_DATABASE), $c->make(self::SERVICE_RUNTIME_CONTEXT)));
            $this->container->singleton(self::SERVICE_DEVICE_ASSIGNMENT_REPOSITORY, static fn (Container $c): \Sentinel\Repositories\DeviceAssignmentRepository => new \Sentinel\Repositories\DeviceAssignmentRepository($c->make(self::SERVICE_DATABASE), $c->make(self::SERVICE_RUNTIME_CONTEXT)));
            $this->container->singleton(self::SERVICE_DEVICE_SESSION_REPOSITORY, static fn (Container $c): \Sentinel\Repositories\DeviceSessionRepository => new \Sentinel\Repositories\DeviceSessionRepository($c->make(self::SERVICE_DATABASE), $c->make(self::SERVICE_RUNTIME_CONTEXT)));
        }
        if (!$this->container->has(self::SERVICE_DEVICE_MANAGEMENT)) {
            $this->container->singleton(self::SERVICE_DEVICE_MANAGEMENT, static fn (Container $c): \Sentinel\Services\DeviceManagementService => new \Sentinel\Services\DeviceManagementService($c->make(self::SERVICE_DEVICE_REPOSITORY), $c->make(self::SERVICE_AUDIT)));
            $this->container->singleton(self::SERVICE_DEVICE_ASSIGNMENT_MANAGEMENT, static fn (Container $c): \Sentinel\Services\DeviceAssignmentManagementService => new \Sentinel\Services\DeviceAssignmentManagementService($c->make(self::SERVICE_DEVICE_ASSIGNMENT_REPOSITORY), $c->make(self::SERVICE_RUNTIME_CONTEXT), $c->make(self::SERVICE_AUDIT)));
            $this->container->singleton(self::SERVICE_DEVICE_SESSION_MANAGEMENT, static fn (Container $c): \Sentinel\Services\DeviceSessionManagementService => new \Sentinel\Services\DeviceSessionManagementService($c->make(self::SERVICE_DEVICE_SESSION_REPOSITORY), $c->make(self::SERVICE_AUDIT)));
        }
        if (!$this->container->has(self::SERVICE_INCIDENT_REPOSITORY)) {
            $this->container->singleton(self::SERVICE_INCIDENT_REPOSITORY, static fn (Container $c): \Sentinel\Repositories\IncidentRepository => new \Sentinel\Repositories\IncidentRepository($c->make(self::SERVICE_DATABASE), $c->make(self::SERVICE_RUNTIME_CONTEXT)));
        }
        if (!$this->container->has(self::SERVICE_INCIDENT_MANAGEMENT)) {
            $this->container->singleton(self::SERVICE_INCIDENT_MANAGEMENT, static fn (Container $c): \Sentinel\Services\IncidentManagementService => new \Sentinel\Services\IncidentManagementService($c->make(self::SERVICE_INCIDENT_REPOSITORY), $c->make(self::SERVICE_RUNTIME_CONTEXT), $c->make(self::SERVICE_AUDIT)));
        }

        if (!$this->container->has('occurrence_book_service')) {
            $this->container->singleton('occurrence_book_repository', static fn (Container $c): \Sentinel\Repositories\OccurrenceBookRepository => new \Sentinel\Repositories\OccurrenceBookRepository($c->make(self::SERVICE_DATABASE), $c->make(self::SERVICE_RUNTIME_CONTEXT)));
            $this->container->singleton('occurrence_book_service', static fn (Container $c): \Sentinel\Services\OccurrenceBookService => new \Sentinel\Services\OccurrenceBookService($c->make('occurrence_book_repository'), $c->make(self::SERVICE_RUNTIME_CONTEXT), $c->make(self::SERVICE_AUDIT)));
        }
        if (!$this->container->has('signup_service')) {
            $this->container->singleton('signup_service', static fn (Container $c): \Sentinel\Services\SignupService => new \Sentinel\Services\SignupService($c->make(self::SERVICE_DATABASE)));
        }
        if (!$this->container->has('operational_repository')) {
            $this->container->singleton('operational_repository', static fn (Container $c): \Sentinel\Repositories\OperationalRepository => new \Sentinel\Repositories\OperationalRepository($c->make(self::SERVICE_DATABASE), $c->make(self::SERVICE_RUNTIME_CONTEXT)));
        }
        if (!$this->container->has('access_management_service')) {
            $this->container->singleton('access_management_service', static fn (Container $c): \Sentinel\Services\AccessManagementService => new \Sentinel\Services\AccessManagementService($c->make('operational_repository'), $c->make(self::SERVICE_RUNTIME_CONTEXT), $c->make(self::SERVICE_AUDIT)));
        }
        if (!$this->container->has('inspection_management_service')) {
            $this->container->singleton('inspection_management_service', static fn (Container $c): \Sentinel\Services\InspectionManagementService => new \Sentinel\Services\InspectionManagementService($c->make('operational_repository'), $c->make(self::SERVICE_RUNTIME_CONTEXT), $c->make(self::SERVICE_AUDIT)));
        }
        if (!$this->container->has('alert_notification_service')) {
            $this->container->singleton('alert_notification_service', static fn (Container $c): \Sentinel\Services\AlertNotificationService => new \Sentinel\Services\AlertNotificationService($c->make('operational_repository'), $c->make(self::SERVICE_RUNTIME_CONTEXT), $c->make(self::SERVICE_AUDIT)));
        }
        if (!$this->container->has('operational_reporting_service')) {
            $this->container->singleton('operational_reporting_service', static fn (Container $c): \Sentinel\Services\OperationalReportingService => new \Sentinel\Services\OperationalReportingService($c->make(self::SERVICE_DATABASE), $c->make(self::SERVICE_RUNTIME_CONTEXT), $c->make(self::SERVICE_CSV_EXPORT)));
        }
        if (!$this->container->has('guard_workflow_service')) {
            $this->container->singleton('guard_workflow_service', static fn (Container $c): \Sentinel\Services\GuardWorkflowService => new \Sentinel\Services\GuardWorkflowService($c->make(self::SERVICE_DATABASE), $c->make(self::SERVICE_RUNTIME_CONTEXT), $c));
        }
        if (!$this->container->has('patrol_schedule_service')) {
            $this->container->singleton('patrol_schedule_service', static fn (Container $c): \Sentinel\Services\PatrolScheduleService => new \Sentinel\Services\PatrolScheduleService($c->make(self::SERVICE_DATABASE), $c->make(self::SERVICE_RUNTIME_CONTEXT), $c->make(self::SERVICE_AUDIT)));
        }
        if (!$this->container->has('supervisor_attention_service')) {
            $this->container->singleton('supervisor_attention_service', static fn (Container $c): \Sentinel\Services\SupervisorAttentionService => new \Sentinel\Services\SupervisorAttentionService($c->make(self::SERVICE_DATABASE), $c->make(self::SERVICE_RUNTIME_CONTEXT)));
        }
        if (!$this->container->has('ai_help_service')) {
            $this->container->singleton('ai_help_service', static fn (Container $c): \Sentinel\Services\AiHelpService => new \Sentinel\Services\AiHelpService($c->make('config')));
        }
        if (!$this->container->has(self::SERVICE_EMPLOYEE_REPOSITORY)) {
            $this->container->singleton(
                self::SERVICE_EMPLOYEE_REPOSITORY,
                static fn (Container $c): \Sentinel\Repositories\EmployeeRepository => new \Sentinel\Repositories\EmployeeRepository(
                    $c->make(self::SERVICE_DATABASE),
                    $c->make(self::SERVICE_RUNTIME_CONTEXT),
                ),
            );
        }

        if (!$this->container->has(self::SERVICE_EMPLOYEE_MANAGEMENT)) {
            $this->container->singleton(
                self::SERVICE_EMPLOYEE_MANAGEMENT,
                static fn (Container $c): \Sentinel\Services\EmployeeManagementService => new \Sentinel\Services\EmployeeManagementService(
                    $c->make(self::SERVICE_EMPLOYEE_REPOSITORY),
                ),
            );
        }

        if (!$this->container->has(self::SERVICE_PERSON_REPOSITORY)) {
            $this->container->singleton(
                self::SERVICE_PERSON_REPOSITORY,
                static fn (Container $c): \Sentinel\Repositories\PersonRepository => new \Sentinel\Repositories\PersonRepository(
                    $c->make(self::SERVICE_DATABASE),
                    $c->make(self::SERVICE_RUNTIME_CONTEXT),
                ),
            );
        }

        if (!$this->container->has(self::SERVICE_PERSON_MANAGEMENT)) {
            $this->container->singleton(
                self::SERVICE_PERSON_MANAGEMENT,
                static fn (Container $c): \Sentinel\Services\PersonManagementService => new \Sentinel\Services\PersonManagementService(
                    $c->make(self::SERVICE_PERSON_REPOSITORY),
                ),
            );
        }

        if (!$this->container->has(self::SERVICE_CLIENT_REPOSITORY)) {
            $this->container->singleton(
                self::SERVICE_CLIENT_REPOSITORY,
                static fn (Container $c): \Sentinel\Repositories\ClientRepository => new \Sentinel\Repositories\ClientRepository(
                    $c->make(self::SERVICE_DATABASE),
                    $c->make(self::SERVICE_RUNTIME_CONTEXT),
                ),
            );
        }

        if (!$this->container->has(self::SERVICE_CLIENT_MANAGEMENT)) {
            $this->container->singleton(
                self::SERVICE_CLIENT_MANAGEMENT,
                static fn (Container $c): \Sentinel\Services\ClientService => new \Sentinel\Services\ClientService(
                    $c->make(self::SERVICE_CLIENT_REPOSITORY),
                ),
            );
        }

        if (!$this->container->has(self::SERVICE_BRANCH_REPOSITORY)) {
            $this->container->singleton(
                self::SERVICE_BRANCH_REPOSITORY,
                static fn (Container $c): \Sentinel\Repositories\BranchRepository => new \Sentinel\Repositories\BranchRepository(
                    $c->make(self::SERVICE_DATABASE),
                    $c->make(self::SERVICE_RUNTIME_CONTEXT),
                ),
            );
        }

        if (!$this->container->has(self::SERVICE_BRANCH_MANAGEMENT)) {
            $this->container->singleton(
                self::SERVICE_BRANCH_MANAGEMENT,
                static fn (Container $c): \Sentinel\Services\BranchManagementService => new \Sentinel\Services\BranchManagementService(
                    $c->make(self::SERVICE_BRANCH_REPOSITORY),
                ),
            );
        }

        if (!$this->container->has(self::SERVICE_DEPARTMENT_REPOSITORY)) {
            $this->container->singleton(
                self::SERVICE_DEPARTMENT_REPOSITORY,
                static fn (Container $c): \Sentinel\Repositories\DepartmentRepository => new \Sentinel\Repositories\DepartmentRepository(
                    $c->make(self::SERVICE_DATABASE),
                    $c->make(self::SERVICE_RUNTIME_CONTEXT),
                ),
            );
        }

        if (!$this->container->has(self::SERVICE_DEPARTMENT_MANAGEMENT)) {
            $this->container->singleton(
                self::SERVICE_DEPARTMENT_MANAGEMENT,
                static fn (Container $c): \Sentinel\Services\DepartmentManagementService => new \Sentinel\Services\DepartmentManagementService(
                    $c->make(self::SERVICE_DEPARTMENT_REPOSITORY),
                ),
            );
        }

        if (!$this->container->has(self::SERVICE_SITE_REPOSITORY)) {
            $this->container->singleton(
                self::SERVICE_SITE_REPOSITORY,
                static fn (Container $c): \Sentinel\Repositories\SiteRepository => new \Sentinel\Repositories\SiteRepository(
                    $c->make(self::SERVICE_DATABASE),
                    $c->make(self::SERVICE_RUNTIME_CONTEXT),
                ),
            );
        }

        if (!$this->container->has(self::SERVICE_SITE_MANAGEMENT)) {
            $this->container->singleton(
                self::SERVICE_SITE_MANAGEMENT,
                static fn (Container $c): \Sentinel\Services\SiteManagementService => new \Sentinel\Services\SiteManagementService(
                    $c->make(self::SERVICE_SITE_REPOSITORY),
                    $c->make('subscription_service'),
                ),
            );
        }

        if (!$this->container->has(self::SERVICE_POSITION_REPOSITORY)) {
            $this->container->singleton(
                self::SERVICE_POSITION_REPOSITORY,
                static fn (Container $c): \Sentinel\Repositories\PositionRepository => new \Sentinel\Repositories\PositionRepository(
                    $c->make(self::SERVICE_DATABASE),
                    $c->make(self::SERVICE_RUNTIME_CONTEXT),
                ),
            );
        }

        if (!$this->container->has(self::SERVICE_POSITION_MANAGEMENT)) {
            $this->container->singleton(
                self::SERVICE_POSITION_MANAGEMENT,
                static fn (Container $c): \Sentinel\Services\PositionManagementService => new \Sentinel\Services\PositionManagementService(
                    $c->make(self::SERVICE_POSITION_REPOSITORY),
                ),
            );
        }

        if (!$this->container->has(self::SERVICE_ASSIGNMENT_REPOSITORY)) {
            $this->container->singleton(
                self::SERVICE_ASSIGNMENT_REPOSITORY,
                static fn (Container $c): \Sentinel\Repositories\EmployeeAssignmentRepository => new \Sentinel\Repositories\EmployeeAssignmentRepository(
                    $c->make(self::SERVICE_DATABASE),
                    $c->make(self::SERVICE_RUNTIME_CONTEXT),
                ),
            );
        }

        if (!$this->container->has(self::SERVICE_ASSIGNMENT_MANAGEMENT)) {
            $this->container->singleton(
                self::SERVICE_ASSIGNMENT_MANAGEMENT,
                static fn (Container $c): \Sentinel\Services\EmployeeAssignmentManagementService => new \Sentinel\Services\EmployeeAssignmentManagementService(
                    $c->make(self::SERVICE_ASSIGNMENT_REPOSITORY),
                ),
            );
        }

        if (!$this->container->has(self::SERVICE_GUARD_REPOSITORY)) {
            $this->container->singleton(
                self::SERVICE_GUARD_REPOSITORY,
                static fn (Container $c): \Sentinel\Repositories\GuardRepository => new \Sentinel\Repositories\GuardRepository(
                    $c->make(self::SERVICE_DATABASE),
                    $c->make(self::SERVICE_RUNTIME_CONTEXT),
                ),
            );
        }

        if (!$this->container->has(self::SERVICE_GUARD_MANAGEMENT)) {
            $this->container->singleton(
                self::SERVICE_GUARD_MANAGEMENT,
                static fn (Container $c): \Sentinel\Services\GuardManagementService => new \Sentinel\Services\GuardManagementService(
                    $c->make(self::SERVICE_GUARD_REPOSITORY),
                    $c->make(self::SERVICE_ASSIGNMENT_MANAGEMENT),
                    $c->make(self::SERVICE_POSITION_REPOSITORY),
                ),
            );
        }

        if (!$this->container->has(self::SERVICE_GUARD_SITE_ASSIGNMENT_REPOSITORY)) {
            $this->container->singleton(
                self::SERVICE_GUARD_SITE_ASSIGNMENT_REPOSITORY,
                static fn (Container $c): \Sentinel\Repositories\GuardSiteAssignmentRepository => new \Sentinel\Repositories\GuardSiteAssignmentRepository(
                    $c->make(self::SERVICE_DATABASE),
                    $c->make(self::SERVICE_RUNTIME_CONTEXT),
                ),
            );
        }

        if (!$this->container->has(self::SERVICE_GUARD_SITE_ASSIGNMENT_MANAGEMENT)) {
            $this->container->singleton(
                self::SERVICE_GUARD_SITE_ASSIGNMENT_MANAGEMENT,
                static fn (Container $c): \Sentinel\Services\GuardSiteAssignmentManagementService => new \Sentinel\Services\GuardSiteAssignmentManagementService(
                    $c->make(self::SERVICE_GUARD_SITE_ASSIGNMENT_REPOSITORY),
                ),
            );
        }

        if (!$this->container->has(self::SERVICE_SHIFT_ASSIGNMENT_REPOSITORY)) {
            $this->container->singleton(
                self::SERVICE_SHIFT_ASSIGNMENT_REPOSITORY,
                static fn (Container $c): \Sentinel\Repositories\ShiftAssignmentRepository => new \Sentinel\Repositories\ShiftAssignmentRepository(
                    $c->make(self::SERVICE_DATABASE),
                    $c->make(self::SERVICE_RUNTIME_CONTEXT),
                ),
            );
        }

        if (!$this->container->has(self::SERVICE_SHIFT_ASSIGNMENT_MANAGEMENT)) {
            $this->container->singleton(
                self::SERVICE_SHIFT_ASSIGNMENT_MANAGEMENT,
                static fn (Container $c): \Sentinel\Services\ShiftAssignmentManagementService => new \Sentinel\Services\ShiftAssignmentManagementService(
                    $c->make(self::SERVICE_SHIFT_ASSIGNMENT_REPOSITORY),
                ),
            );
        }

        if (!$this->container->has(self::SERVICE_ATTENDANCE_REPOSITORY)) {
            $this->container->singleton(
                self::SERVICE_ATTENDANCE_REPOSITORY,
                static fn (Container $c): \Sentinel\Repositories\AttendanceRepository => new \Sentinel\Repositories\AttendanceRepository(
                    $c->make(self::SERVICE_DATABASE),
                    $c->make(self::SERVICE_RUNTIME_CONTEXT),
                ),
            );
        }

        if (!$this->container->has(self::SERVICE_ATTENDANCE_MANAGEMENT)) {
            $this->container->singleton(
                self::SERVICE_ATTENDANCE_MANAGEMENT,
                static fn (Container $c): \Sentinel\Services\AttendanceManagementService => new \Sentinel\Services\AttendanceManagementService(
                    $c->make(self::SERVICE_ATTENDANCE_REPOSITORY),
                ),
            );
        }

        if (!$this->container->has(self::SERVICE_PATROL_ROUTE_REPOSITORY)) {
            $this->container->singleton(
                self::SERVICE_PATROL_ROUTE_REPOSITORY,
                static fn (Container $c): \Sentinel\Repositories\PatrolRouteRepository => new \Sentinel\Repositories\PatrolRouteRepository(
                    $c->make(self::SERVICE_DATABASE),
                    $c->make(self::SERVICE_RUNTIME_CONTEXT),
                ),
            );
        }

        if (!$this->container->has(self::SERVICE_PATROL_ROUTE_MANAGEMENT)) {
            $this->container->singleton(
                self::SERVICE_PATROL_ROUTE_MANAGEMENT,
                static fn (Container $c): \Sentinel\Services\PatrolRouteManagementService => new \Sentinel\Services\PatrolRouteManagementService(
                    $c->make(self::SERVICE_PATROL_ROUTE_REPOSITORY),
                ),
            );
        }

        if (!$this->container->has(self::SERVICE_PATROL_CHECKPOINT_REPOSITORY)) {
            $this->container->singleton(
                self::SERVICE_PATROL_CHECKPOINT_REPOSITORY,
                static fn (Container $c): \Sentinel\Repositories\PatrolCheckpointRepository => new \Sentinel\Repositories\PatrolCheckpointRepository(
                    $c->make(self::SERVICE_DATABASE),
                    $c->make(self::SERVICE_RUNTIME_CONTEXT),
                ),
            );
        }

        if (!$this->container->has(self::SERVICE_PATROL_CHECKPOINT_MANAGEMENT)) {
            $this->container->singleton(
                self::SERVICE_PATROL_CHECKPOINT_MANAGEMENT,
                static fn (Container $c): \Sentinel\Services\PatrolCheckpointManagementService => new \Sentinel\Services\PatrolCheckpointManagementService(
                    $c->make(self::SERVICE_PATROL_CHECKPOINT_REPOSITORY),
                ),
            );
        }

        if (!$this->container->has(self::SERVICE_PATROL_SESSION_REPOSITORY)) {
            $this->container->singleton(
                self::SERVICE_PATROL_SESSION_REPOSITORY,
                static fn (Container $c): \Sentinel\Repositories\PatrolSessionRepository => new \Sentinel\Repositories\PatrolSessionRepository(
                    $c->make(self::SERVICE_DATABASE),
                    $c->make(self::SERVICE_RUNTIME_CONTEXT),
                ),
            );
        }

        if (!$this->container->has(self::SERVICE_PATROL_SESSION_MANAGEMENT)) {
            $this->container->singleton(
                self::SERVICE_PATROL_SESSION_MANAGEMENT,
                static fn (Container $c): \Sentinel\Services\PatrolSessionManagementService => new \Sentinel\Services\PatrolSessionManagementService(
                    $c->make(self::SERVICE_PATROL_SESSION_REPOSITORY),
                ),
            );
        }

        if (!$this->container->has(self::SERVICE_PATROL_CHECKPOINT_EVENT_REPOSITORY)) {
            $this->container->singleton(
                self::SERVICE_PATROL_CHECKPOINT_EVENT_REPOSITORY,
                static fn (Container $c): \Sentinel\Repositories\PatrolCheckpointEventRepository => new \Sentinel\Repositories\PatrolCheckpointEventRepository(
                    $c->make(self::SERVICE_DATABASE),
                    $c->make(self::SERVICE_RUNTIME_CONTEXT),
                ),
            );
        }

        if (!$this->container->has(self::SERVICE_PATROL_CHECKPOINT_EVENT_MANAGEMENT)) {
            $this->container->singleton(
                self::SERVICE_PATROL_CHECKPOINT_EVENT_MANAGEMENT,
                static fn (Container $c): \Sentinel\Services\PatrolCheckpointEventManagementService => new \Sentinel\Services\PatrolCheckpointEventManagementService(
                    $c->make(self::SERVICE_PATROL_CHECKPOINT_EVENT_REPOSITORY),
                ),
            );
        }

        if (!$this->container->has(self::SERVICE_LOCATION_REPOSITORY)) {
            $this->container->singleton(
                self::SERVICE_LOCATION_REPOSITORY,
                static fn (Container $c): \Sentinel\Repositories\LocationRepository => new \Sentinel\Repositories\LocationRepository(
                    $c->make(self::SERVICE_DATABASE),
                    $c->make(self::SERVICE_RUNTIME_CONTEXT),
                ),
            );
        }

        if (!$this->container->has(self::SERVICE_LOCATION_MANAGEMENT)) {
            $this->container->singleton(
                self::SERVICE_LOCATION_MANAGEMENT,
                static fn (Container $c): \Sentinel\Services\LocationManagementService => new \Sentinel\Services\LocationManagementService(
                    $c->make(self::SERVICE_LOCATION_REPOSITORY),
                ),
            );
        }

        if (!$this->container->has(self::SERVICE_GEOFENCE_REPOSITORY)) {
            $this->container->singleton(
                self::SERVICE_GEOFENCE_REPOSITORY,
                static fn (Container $c): \Sentinel\Repositories\GeofenceRepository => new \Sentinel\Repositories\GeofenceRepository(
                    $c->make(self::SERVICE_DATABASE),
                    $c->make(self::SERVICE_RUNTIME_CONTEXT),
                ),
            );
        }

        if (!$this->container->has(self::SERVICE_GEOFENCE_MANAGEMENT)) {
            $this->container->singleton(
                self::SERVICE_GEOFENCE_MANAGEMENT,
                static fn (Container $c): \Sentinel\Services\GeofenceManagementService => new \Sentinel\Services\GeofenceManagementService(
                    $c->make(self::SERVICE_GEOFENCE_REPOSITORY),
                ),
            );
        }

        if (!$this->container->has(self::SERVICE_CONTRACT_REPOSITORY)) {
            $this->container->singleton(self::SERVICE_CONTRACT_REPOSITORY, static fn (Container $c): \Sentinel\Repositories\ContractRepository => new \Sentinel\Repositories\ContractRepository($c->make(self::SERVICE_DATABASE), $c->make(self::SERVICE_RUNTIME_CONTEXT)));
        }
        if (!$this->container->has(self::SERVICE_CONTRACT_MANAGEMENT)) {
            $this->container->singleton(self::SERVICE_CONTRACT_MANAGEMENT, static fn (Container $c): \Sentinel\Services\ContractManagementService => new \Sentinel\Services\ContractManagementService($c->make(self::SERVICE_CONTRACT_REPOSITORY)));
        }
        if (!$this->container->has(self::SERVICE_POST_REPOSITORY)) {
            $this->container->singleton(self::SERVICE_POST_REPOSITORY, static fn (Container $c): \Sentinel\Repositories\PostRepository => new \Sentinel\Repositories\PostRepository($c->make(self::SERVICE_DATABASE), $c->make(self::SERVICE_RUNTIME_CONTEXT)));
        }
        if (!$this->container->has(self::SERVICE_POST_MANAGEMENT)) {
            $this->container->singleton(self::SERVICE_POST_MANAGEMENT, static fn (Container $c): \Sentinel\Services\PostManagementService => new \Sentinel\Services\PostManagementService($c->make(self::SERVICE_POST_REPOSITORY)));
        }

        if (!$this->container->has(self::SERVICE_PASSWORD_AUTHENTICATOR)) {
            $this->container->singleton(
                self::SERVICE_PASSWORD_AUTHENTICATOR,
                static fn (Container $c): \Sentinel\Security\PasswordAuthenticator => new \Sentinel\Security\PasswordAuthenticator($c->make(self::SERVICE_USER_REPOSITORY)),
            );
        }

        if (!$this->container->has(self::SERVICE_LOGIN)) {
            $this->container->singleton(
                self::SERVICE_LOGIN,
                static fn (Container $c): \Sentinel\Security\LoginService => new \Sentinel\Security\LoginService(
                    $c->make(self::SERVICE_PASSWORD_AUTHENTICATOR),
                    $c->make(self::SERVICE_JWT),
                    $c->make('session_security_service'),
                ),
            );
        }

        if (!$this->container->has(self::SERVICE_ACCOUNT_SECURITY)) {
            $this->container->singleton(
                self::SERVICE_ACCOUNT_SECURITY,
                static fn (Container $c): \Sentinel\Services\AccountSecurityService => new \Sentinel\Services\AccountSecurityService(
                    $c->make(self::SERVICE_USER_REPOSITORY),
                    $c->make(self::SERVICE_RUNTIME_CONTEXT),
                ),
            );
        }

        if (!$this->container->has(self::SERVICE_AUDIT_REPOSITORY)) {
            $this->container->singleton(self::SERVICE_AUDIT_REPOSITORY, static fn (Container $c): \Sentinel\Repositories\AuditRepository => new \Sentinel\Repositories\AuditRepository($c->make(self::SERVICE_DATABASE), $c->make(self::SERVICE_RUNTIME_CONTEXT)));
        }
        if (!$this->container->has(self::SERVICE_AUDIT)) {
            $this->container->singleton(self::SERVICE_AUDIT, static fn (Container $c): \Sentinel\Services\AuditService => new \Sentinel\Services\AuditService($c->make(self::SERVICE_AUDIT_REPOSITORY), $c->make(self::SERVICE_RUNTIME_CONTEXT)));
        }

        if (!$this->routesLoaded) {
            $this->loadRoutes();
            $this->routesLoaded = true;
        }

        return $this;
    }

    public function container(): Container
    {
        return $this->container;
    }

    public function config(): Config
    {
        return $this->container->make(self::SERVICE_CONFIG);
    }

    public function logger(): Logger
    {
        return $this->container->make(self::SERVICE_LOGGER);
    }

    public function database(): Database
    {
        return $this->container->make(self::SERVICE_DATABASE);
    }

    public function runtimeContext(): RuntimeContext
    {
        return $this->container->make(self::SERVICE_RUNTIME_CONTEXT);
    }

    public function router(): Router
    {
        return $this->container->make(self::SERVICE_ROUTER);
    }

    public function middlewarePipeline(): MiddlewarePipeline
    {
        return $this->container->make(self::SERVICE_MIDDLEWARE_PIPELINE);
    }

    public function jwt(): \Sentinel\Security\JwtService
    {
        return $this->container->make(self::SERVICE_JWT);
    }

    public function authenticationMiddleware(): \Sentinel\Security\AuthenticationMiddleware
    {
        return $this->container->make(self::SERVICE_AUTHENTICATION_MIDDLEWARE);
    }

    public function permissionRepository(): \Sentinel\Repositories\PermissionRepository
    {
        return $this->container->make(self::SERVICE_PERMISSION_REPOSITORY);
    }

    public function roleRepository(): \Sentinel\Repositories\RoleRepository
    {
        return $this->container->make(self::SERVICE_ROLE_REPOSITORY);
    }

    public function authorizationService(): \Sentinel\Security\AuthorizationService
    {
        return $this->container->make(self::SERVICE_AUTHORIZATION_SERVICE);
    }

    public function authorizationMiddleware(): \Sentinel\Security\AuthorizationMiddleware
    {
        return $this->container->make(self::SERVICE_AUTHORIZATION_MIDDLEWARE);
    }

    public function tenantContextService(): \Sentinel\Security\TenantContextService
    {
        return $this->container->make(self::SERVICE_TENANT_CONTEXT_SERVICE);
    }

    public function tenantMiddleware(): \Sentinel\Security\TenantMiddleware
    {
        return $this->container->make(self::SERVICE_TENANT_MIDDLEWARE);
    }

    public function userRepository(): \Sentinel\Repositories\UserRepository
    {
        return $this->container->make(self::SERVICE_USER_REPOSITORY);
    }

    public function userManagementService(): \Sentinel\Services\UserManagementService
    {
        return $this->container->make(self::SERVICE_USER_MANAGEMENT);
    }

    public function employeeRepository(): \Sentinel\Repositories\EmployeeRepository
    {
        return $this->container->make(self::SERVICE_EMPLOYEE_REPOSITORY);
    }

    public function employeeManagementService(): \Sentinel\Services\EmployeeManagementService
    {
        return $this->container->make(self::SERVICE_EMPLOYEE_MANAGEMENT);
    }

    public function personRepository(): \Sentinel\Repositories\PersonRepository
    {
        return $this->container->make(self::SERVICE_PERSON_REPOSITORY);
    }

    public function personManagementService(): \Sentinel\Services\PersonManagementService
    {
        return $this->container->make(self::SERVICE_PERSON_MANAGEMENT);
    }

    public function clientRepository(): \Sentinel\Repositories\ClientRepository
    {
        return $this->container->make(self::SERVICE_CLIENT_REPOSITORY);
    }

    public function clientManagementService(): \Sentinel\Services\ClientService
    {
        return $this->container->make(self::SERVICE_CLIENT_MANAGEMENT);
    }

    public function branchRepository(): \Sentinel\Repositories\BranchRepository
    {
        return $this->container->make(self::SERVICE_BRANCH_REPOSITORY);
    }

    public function branchManagementService(): \Sentinel\Services\BranchManagementService
    {
        return $this->container->make(self::SERVICE_BRANCH_MANAGEMENT);
    }

    public function departmentRepository(): \Sentinel\Repositories\DepartmentRepository
    {
        return $this->container->make(self::SERVICE_DEPARTMENT_REPOSITORY);
    }

    public function departmentManagementService(): \Sentinel\Services\DepartmentManagementService
    {
        return $this->container->make(self::SERVICE_DEPARTMENT_MANAGEMENT);
    }

    public function siteRepository(): \Sentinel\Repositories\SiteRepository
    {
        return $this->container->make(self::SERVICE_SITE_REPOSITORY);
    }

    public function siteManagementService(): \Sentinel\Services\SiteManagementService
    {
        return $this->container->make(self::SERVICE_SITE_MANAGEMENT);
    }

    public function positionRepository(): \Sentinel\Repositories\PositionRepository
    {
        return $this->container->make(self::SERVICE_POSITION_REPOSITORY);
    }

    public function positionManagementService(): \Sentinel\Services\PositionManagementService
    {
        return $this->container->make(self::SERVICE_POSITION_MANAGEMENT);
    }

    public function employeeAssignmentRepository(): \Sentinel\Repositories\EmployeeAssignmentRepository
    {
        return $this->container->make(self::SERVICE_ASSIGNMENT_REPOSITORY);
    }

    public function employeeAssignmentManagementService(): \Sentinel\Services\EmployeeAssignmentManagementService
    {
        return $this->container->make(self::SERVICE_ASSIGNMENT_MANAGEMENT);
    }

    public function guardRepository(): \Sentinel\Repositories\GuardRepository
    {
        return $this->container->make(self::SERVICE_GUARD_REPOSITORY);
    }

    public function guardManagementService(): \Sentinel\Services\GuardManagementService
    {
        return $this->container->make(self::SERVICE_GUARD_MANAGEMENT);
    }

    public function guardSiteAssignmentRepository(): \Sentinel\Repositories\GuardSiteAssignmentRepository
    {
        return $this->container->make(self::SERVICE_GUARD_SITE_ASSIGNMENT_REPOSITORY);
    }

    public function guardSiteAssignmentManagementService(): \Sentinel\Services\GuardSiteAssignmentManagementService
    {
        return $this->container->make(self::SERVICE_GUARD_SITE_ASSIGNMENT_MANAGEMENT);
    }

    public function shiftAssignmentRepository(): \Sentinel\Repositories\ShiftAssignmentRepository
    {
        return $this->container->make(self::SERVICE_SHIFT_ASSIGNMENT_REPOSITORY);
    }

    public function shiftAssignmentManagementService(): \Sentinel\Services\ShiftAssignmentManagementService
    {
        return $this->container->make(self::SERVICE_SHIFT_ASSIGNMENT_MANAGEMENT);
    }

    public function attendanceRepository(): \Sentinel\Repositories\AttendanceRepository
    {
        return $this->container->make(self::SERVICE_ATTENDANCE_REPOSITORY);
    }

    public function attendanceManagementService(): \Sentinel\Services\AttendanceManagementService
    {
        return $this->container->make(self::SERVICE_ATTENDANCE_MANAGEMENT);
    }

    public function patrolRouteManagementService(): \Sentinel\Services\PatrolRouteManagementService
    {
        return $this->container->make(self::SERVICE_PATROL_ROUTE_MANAGEMENT);
    }

    public function patrolCheckpointManagementService(): \Sentinel\Services\PatrolCheckpointManagementService
    {
        return $this->container->make(self::SERVICE_PATROL_CHECKPOINT_MANAGEMENT);
    }

    public function patrolSessionManagementService(): \Sentinel\Services\PatrolSessionManagementService
    {
        return $this->container->make(self::SERVICE_PATROL_SESSION_MANAGEMENT);
    }

    public function patrolCheckpointEventManagementService(): \Sentinel\Services\PatrolCheckpointEventManagementService
    {
        return $this->container->make(self::SERVICE_PATROL_CHECKPOINT_EVENT_MANAGEMENT);
    }

    public function locationRepository(): \Sentinel\Repositories\LocationRepository
    {
        return $this->container->make(self::SERVICE_LOCATION_REPOSITORY);
    }

    public function locationManagementService(): \Sentinel\Services\LocationManagementService
    {
        return $this->container->make(self::SERVICE_LOCATION_MANAGEMENT);
    }

    public function geofenceRepository(): \Sentinel\Repositories\GeofenceRepository
    {
        return $this->container->make(self::SERVICE_GEOFENCE_REPOSITORY);
    }

    public function geofenceManagementService(): \Sentinel\Services\GeofenceManagementService
    {
        return $this->container->make(self::SERVICE_GEOFENCE_MANAGEMENT);
    }

    public function contractManagementService(): \Sentinel\Services\ContractManagementService
    {
        return $this->container->make(self::SERVICE_CONTRACT_MANAGEMENT);
    }

    public function postManagementService(): \Sentinel\Services\PostManagementService
    {
        return $this->container->make(self::SERVICE_POST_MANAGEMENT);
    }

    public function passwordAuthenticator(): \Sentinel\Security\PasswordAuthenticator
    {
        return $this->container->make(self::SERVICE_PASSWORD_AUTHENTICATOR);
    }

    public function loginService(): \Sentinel\Security\LoginService
    {
        return $this->container->make(self::SERVICE_LOGIN);
    }

    public function accountSecurityService(): \Sentinel\Services\AccountSecurityService
    {
        return $this->container->make(self::SERVICE_ACCOUNT_SECURITY);
    }

    public function auditService(): \Sentinel\Services\AuditService
    {
        return $this->container->make(self::SERVICE_AUDIT);
    }

    public function handle(Request $request): Response
    {
        $context = $this->runtimeContext();
        $request->assertJsonBodyIsAcceptable((int) $this->config()->get('security.max_json_body_bytes', 1048576));
        // A long-lived worker must not carry authentication state into a new request.
        $context->reset();
        $context->setRequest($request);

        $response = $this->middlewarePipeline()->handle(
            $request,
            function (Request $routedRequest) use ($context): Response {
                $context->setRequest($routedRequest);

                return $this->router()->dispatch(
                    $routedRequest,
                    function (Request $authenticatedRequest, callable $next) use ($context): Response {
                        return $this->authenticationMiddleware()->process($authenticatedRequest, $next);
                    },
                    function (Request $authorizedRequest, callable $next, string $permission) use ($context): Response {
                        return $this->authorizationMiddleware()->forPermission($permission)->process($authorizedRequest, function (Request $request) use ($next, $permission): Response { return (new \Sentinel\Services\AuditedMutationService($this->database(), $this->runtimeContext(), $this->container->make(self::SERVICE_AUDIT)))->handle($request, $permission, $next); });
                    },
                    function (Request $tenantRequest, callable $next) use ($context): Response {
                        return $this->tenantMiddleware()->process($tenantRequest, $next);
                    },
                );
            },
        );

        return $response
            ->withHeader('X-Request-Id', $context->requestId())
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('X-Frame-Options', 'DENY')
            ->withHeader('Referrer-Policy', 'no-referrer');
    }

    private function loadRoutes(): void
    {
        foreach (['web.php', 'api.php'] as $routeFile) {
            $register = require $this->basePath . '/routes/' . $routeFile;
            if (!is_callable($register)) {
                throw new \UnexpectedValueException("Route file {$routeFile} must return a callable.");
            }
            $register($this->router(), $this->container);
        }
    }
}

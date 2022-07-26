<?php

use Adldap\Laravel\Events\Authenticated;
use Adldap\Laravel\Events\AuthenticatedModelTrashed;
use Adldap\Laravel\Events\AuthenticatedWithWindows;
use Adldap\Laravel\Events\Authenticating;
use Adldap\Laravel\Events\AuthenticationFailed;
use Adldap\Laravel\Events\AuthenticationRejected;
use Adldap\Laravel\Events\AuthenticationSuccessful;
use Adldap\Laravel\Events\DiscoveredWithCredentials;
use Adldap\Laravel\Events\Importing;
use Adldap\Laravel\Events\Synchronized;
use Adldap\Laravel\Events\Synchronizing;
use Adldap\Laravel\Listeners\LogAuthenticated;
use Adldap\Laravel\Listeners\LogAuthentication;
use Adldap\Laravel\Listeners\LogAuthenticationFailure;
use Adldap\Laravel\Listeners\LogAuthenticationRejection;
use Adldap\Laravel\Listeners\LogAuthenticationSuccess;
use Adldap\Laravel\Listeners\LogDiscovery;
use Adldap\Laravel\Listeners\LogImport;
use Adldap\Laravel\Listeners\LogSynchronized;
use Adldap\Laravel\Listeners\LogSynchronizing;
use Adldap\Laravel\Listeners\LogTrashedModel;
use Adldap\Laravel\Listeners\LogWindowsAuth;

return [

    /*
    |--------------------------------------------------------------------------
    | Connection
    |--------------------------------------------------------------------------
    |
    | The LDAP connection to use for Laravel authentication.
    |
    | You must specify connections in your `config/ldap.php` configuration file.
    |
    */

    'connection' => env('LDAP_CONNECTION', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Provider
    |--------------------------------------------------------------------------
    |
    | The LDAP authentication provider to use depending
    | if you require database synchronization.
    |
    | For synchronizing LDAP users to your local applications database, use the provider:
    |
    | Adldap\Laravel\Auth\DatabaseUserProvider::class
    |
    | Otherwise, if you just require LDAP authentication, use the provider:
    |
    | Adldap\Laravel\Auth\NoDatabaseUserProvider::class
    |
    */

    'provider' => Adldap\Laravel\Auth\DatabaseUserProvider::class,

    /*
    |--------------------------------------------------------------------------
    | Model
    |--------------------------------------------------------------------------
    |
    | The model to utilize for authentication and importing.
    |
    | This option is only applicable to the DatabaseUserProvider.
    |
    */

    'model' => App\Models\User::class,

    /*
    |--------------------------------------------------------------------------
    | Rules
    |--------------------------------------------------------------------------
    |
    | Rules allow you to control user authentication requests depending on scenarios.
    |
    | You can create your own rules and insert them here.
    |
    | All rules must extend from the following class:
    |
    |   Adldap\Laravel\Validation\Rules\Rule
    |
    */

    'rules' => [

        // Denys deleted users from authenticating.

        Adldap\Laravel\Validation\Rules\DenyTrashed::class,

        // Allows only manually imported users to authenticate.

        // Adldap\Laravel\Validation\Rules\OnlyImported::class,

    ],

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    |
    | Scopes allow you to restrict the LDAP query that locates
    | users upon import and authentication.
    |
    | All scopes must implement the following interface:
    |
    |   Adldap\Laravel\Scopes\ScopeInterface
    |
    */

    'scopes' => [

        // Only allows users with a user principal name to authenticate.
        // Suitable when using ActiveDirectory.
        // Adldap\Laravel\Scopes\UpnScope::class,

        // Only allows users with a uid to authenticate.
        // Suitable when using OpenLDAP.
        // Adldap\Laravel\Scopes\UidScope::class,

    ],

    'identifiers' => [

        /*
        |--------------------------------------------------------------------------
        | LDAP
        |--------------------------------------------------------------------------
        |
        | Locate Users By:
        |
        |   This value is the users attribute you would like to locate LDAP
        |   users by in your directory.
        |
        |   For example, using the default configuration below, if you're
        |   authenticating users with an email address, your LDAP server
        |   will be queried for a user with the a `userprincipalname`
        |   equal to the entered email address.
        |
        | Bind Users By:
        |
        |   This value is the users attribute you would
        |   like to use to bind to your LDAP server.
        |
        |   For example, when a user is located by the above attribute,
        |   the users attribute you specify below will be used as
        |   the 'username' to bind to your LDAP server.
        |
        |   This is usually their distinguished name.
        |
        */

        'ldap' => [

            'locate_users_by' => 'userprincipalname',

            'bind_users_by' => 'distinguishedname',

        ],

        'database' => [

            /*
            |--------------------------------------------------------------------------
            | GUID Column
            |--------------------------------------------------------------------------
            |
            | The value of this option is the database column that will contain the
            | LDAP users global identifier. This column does not need to be added
            | to the sync attributes below. It is synchronized automatically.
            |
            | This option is only applicable to the DatabaseUserProvider.
            |
            */

            'guid_column' => 'objectguid',

            /*
            |--------------------------------------------------------------------------
            | Username Column
            |--------------------------------------------------------------------------
            |
            | The value of this option is the database column that contains your
            | users login username.
            |
            | This column must be added to your sync attributes below to be
            | properly synchronized.
            |
            | This option is only applicable to the DatabaseUserProvider.
            |
            */

            'username_column' => 'email',

        ],

        /*
        |--------------------------------------------------------------------------
        | Windows Authentication Middleware (SSO)
        |--------------------------------------------------------------------------
        |
        | Local Users By:
        |
        |   This value is the users attribute you would like to locate LDAP
        |   users by in your directory.
        |
        |   For example, if 'samaccountname' is the value, then your LDAP server is
        |   queried for a user with the 'samaccountname' equal to the value of
        |   $_SERVER['AUTH_USER'].
        |
        |   If a user is found, they are imported (if using the DatabaseUserProvider)
        |   into your local database, then logged in.
        |
        | Server Key:
        |
        |    This value represents the 'key' of the $_SERVER
        |    array to pull the users account name from.
        |
        |    For example, $_SERVER['AUTH_USER'].
        |
        */

        'windows' => [

            'locate_users_by' => 'samaccountname',

            'server_key' => 'AUTH_USER',

        ],

    ],

    'passwords' => [

        /*
        |--------------------------------------------------------------------------
        | Password Sync
        |--------------------------------------------------------------------------
        |
        | The password sync option allows you to automatically synchronize users
        | LDAP passwords to your local database. These passwords are hashed
        | natively by Laravel using the Hash::make() method.
        |
        | Enabling this option would also allow users to login to their accounts
        | using the password last used when an LDAP connection was present.
        |
        | If this option is disabled, the local database account is applied a
        | random 16 character hashed password upon first login, and will
        | lose access to this account upon loss of LDAP connectivity.
        |
        | This option is only applicable to the DatabaseUserProvider.
        |
        */

        'sync' => env('LDAP_PASSWORD_SYNC', false),

        /*
        |--------------------------------------------------------------------------
        | Column
        |--------------------------------------------------------------------------
        |
        | This is the column of your users database table
        | that is used to store passwords.
        |
        | Set this to `null` if you do not have a password column.
        |
        | This option is only applicable to the DatabaseUserProvider.
        |
        */

        'column' => 'password',

    ],

    /*
    |--------------------------------------------------------------------------
    | Login Fallback
    |--------------------------------------------------------------------------
    |
    | The login fallback option allows you to login as a user located in the
    | local database if active directory authentication fails.
    |
    | Set this to true if you would like to enable it.
    |
    | This option is only applicable to the DatabaseUserProvider.
    |
    */

    'login_fallback' => env('LDAP_LOGIN_FALLBACK', false),

    /*
    |--------------------------------------------------------------------------
    | Sync Attributes
    |--------------------------------------------------------------------------
    |
    | Attributes specified here will be added / replaced on the user model
    | upon login, automatically synchronizing and keeping the attributes
    | up to date.
    |
    | The array key represents the users Laravel model key, and
    | the value represents the users LDAP attribute.
    |
    | You **must** include the users login attribute here.
    |
    | This option is only applicable to the DatabaseUserProvider.
    |
    */

    'sync_attributes' => [

        'email' => 'mail',

        'name' => 'cn',

    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | User authentication attempts will be logged using Laravel's
    | default logger if this setting is enabled.
    |
    | No credentials are logged, only usernames.
    |
    | This is usually stored in the '/storage/logs' directory
    | in the root of your application.
    |
    | This option is useful for debugging as well as auditing.
    |
    | You can freely remove any events you would not like to log below,
    | as well as use your own listeners if you would prefer.
    |
    */

    'logging' => [

        'enabled' => env('LDAP_LOGGING', true),

        'events' => [

            Importing::class                 => LogImport::class,
            Synchronized::class              => LogSynchronized::class,
            Synchronizing::class             => LogSynchronizing::class,
            Authenticated::class             => LogAuthenticated::class,
            Authenticating::class            => LogAuthentication::class,
            AuthenticationFailed::class      => LogAuthenticationFailure::class,
            AuthenticationRejected::class    => LogAuthenticationRejection::class,
            AuthenticationSuccessful::class  => LogAuthenticationSuccess::class,
            DiscoveredWithCredentials::class => LogDiscovery::class,
            AuthenticatedWithWindows::class  => LogWindowsAuth::class,
            AuthenticatedModelTrashed::class => LogTrashedModel::class,

        ],
    ],

    /**
     * ldap中的唯一属性名称，推荐使用邮箱地址
     */
    'unique_name' => 'mail'
];

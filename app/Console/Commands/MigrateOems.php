<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;

class MigrateOems extends Command
{
    protected $signature = 'migrate:oems';
    protected $description = 'Run migrations for all OEM databases';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // Get the list of OEM databases from the superadmin database
        $oems = DB::connection('superadmin')->table('database_settings')->get();
         
        foreach ($oems as $oem) {
            // Dynamically configure OEM database connection
            Config::set('database.connections.oem_template.host', $oem->db_host);
            Config::set('database.connections.oem_template.database', $oem->db_name);
            Config::set('database.connections.oem_template.username', $oem->db_username);
            Config::set('database.connections.oem_template.password', $oem->db_password);

            // Run the migration for this OEM's database
            $this->info("Running migrations for OEM: {$oem->name}");

            try {
                Artisan::call('migrate', [
                    '--path' => 'database/migrations/oem',
                    '--database' => 'oem_template',
                ]);
                $this->info("Migrations successful for OEM: {$oem->name}");
            } catch (\Exception $e) {
                $this->error("Failed to migrate for OEM: {$oem->name}. Error: {$e->getMessage()}");
            }
        }
    }
}
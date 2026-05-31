<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CrownRolesSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $customPages = [
            'View:ViewBom',
            'View:ViewShortage',
            'View:ViewWbs',
            'View:ViewShipmentReport',
        ];

        foreach ($customPages as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::all());

        $production = Role::firstOrCreate(['name' => 'production', 'guard_name' => 'web']);
        $production->syncPermissions([
            'ViewAny:Project', 'View:Project', 'Create:Project', 'Update:Project',
            'ViewAny:CatalogSection', 'View:CatalogSection',
            'ViewAny:CatalogItem', 'View:CatalogItem', 'Update:CatalogItem',
            'View:ViewBom', 'View:ViewWbs',
            'ViewAny:WorkOrder', 'View:WorkOrder', 'Create:WorkOrder', 'Update:WorkOrder',
        ]);

        $logistics = Role::firstOrCreate(['name' => 'logistics', 'guard_name' => 'web']);
        $logistics->syncPermissions([
            'ViewAny:Project', 'View:Project', 'Update:Project',
            'View:ViewShortage', 'View:ViewShipmentReport',
        ]);

        $purchasing = Role::firstOrCreate(['name' => 'purchasing', 'guard_name' => 'web']);
        $purchasing->syncPermissions([
            'ViewAny:Supplier', 'View:Supplier', 'Create:Supplier', 'Update:Supplier',
            'ViewAny:PurchaseOrder', 'View:PurchaseOrder', 'Create:PurchaseOrder', 'Update:PurchaseOrder',
            'ViewAny:RawMaterial', 'View:RawMaterial',
        ]);

        $warehouseManager = Role::firstOrCreate(['name' => 'warehouse_manager', 'guard_name' => 'web']);
        $warehouseManager->syncPermissions([
            'ViewAny:Supplier', 'View:Supplier',
            'ViewAny:PurchaseOrder', 'View:PurchaseOrder', 'Update:PurchaseOrder',
            'ViewAny:Warehouse', 'View:Warehouse', 'Create:Warehouse', 'Update:Warehouse',
            'ViewAny:RawMaterial', 'View:RawMaterial', 'Create:RawMaterial', 'Update:RawMaterial',
            'ViewAny:StockBalance', 'View:StockBalance',
            'ViewAny:StockMovement', 'View:StockMovement',
            'ViewAny:MaterialRequest', 'View:MaterialRequest', 'Update:MaterialRequest',
            'ViewAny:FinishedReceipt', 'View:FinishedReceipt', 'Update:FinishedReceipt',
            'ViewAny:WorkOrder', 'View:WorkOrder',
        ]);

        $viewer = Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'web']);
        $viewerPerms = Permission::query()
            ->where(function ($query) {
                $query->where('name', 'like', 'View:%')
                    ->orWhere('name', 'like', 'ViewAny:%');
            })
            ->whereNotIn('name', ['View:Role', 'ViewAny:Role', 'View:User', 'ViewAny:User'])
            ->pluck('name')
            ->all();
        $viewer->syncPermissions($viewerPerms);

        $this->seedDemoUsers();
        $this->assignAdminRole();
    }

    protected function assignAdminRole(): void
    {
        $user = \App\Models\User::query()
            ->where('email', env('ADMIN_EMAIL', 'admin@crown-bom.test'))
            ->first();

        if ($user) {
            $user->syncRoles(['admin']);
        }
    }

    protected function seedDemoUsers(): void
    {
        $users = [
            ['email' => 'production@crown-bom.test', 'name' => 'مسؤول الحصر', 'role' => 'production'],
            ['email' => 'logistics@crown-bom.test', 'name' => 'مسؤول التوريد', 'role' => 'logistics'],
            ['email' => 'viewer@crown-bom.test', 'name' => 'مشاهد', 'role' => 'viewer'],
            ['email' => 'warehouse@crown-bom.test', 'name' => 'مدير المخازن', 'role' => 'warehouse_manager'],
            ['email' => 'purchasing@crown-bom.test', 'name' => 'مسؤول المشتريات', 'role' => 'purchasing'],
        ];

        foreach ($users as $data) {
            $user = \App\Models\User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name'              => $data['name'],
                    'password'          => 'password',
                    'is_active'         => true,
                    'email_verified_at' => now(),
                ]
            );
            $user->syncRoles([$data['role']]);
        }
    }
}

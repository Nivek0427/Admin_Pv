<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        //region permisos
        //permiso vista inventario
        $permInventario = Permission::firstOrCreate([
            'name' => 'inventario',
            'guard_name' => 'web'
        ]);
        //permiso vista reportes
        $permReportes = Permission::firstOrCreate([
            'name' => 'reportes',
            'guard_name' => 'web'
        ]);
        //permiso productoCreate
        $permProductoCreate = Permission::firstOrCreate([
            'name' => 'productos.create',
            'guard_name' => 'web'
        ]);
        //permiso producto edit
        $permProductoEdit = Permission::firstOrCreate([
            'name' => 'productos.edit',
            'guard_name' => 'web'
        ]);
        //permiso producto delete
        $permProductoDelete = Permission::firstOrCreate([
            'name' => 'productos.delete',
            'guard_name' => 'web'
        ]);
        //permiso acciones
        $permActions = Permission::firstOrCreate([
            'name' => 'acciones',
            'guard_name' => 'web'
        ]);
        //permiso ventas.revocar
        $permVentasRevocar = Permission::firstOrCreate([
            'name' => 'ventas.revocar',
            'guard_name' => 'web'
        ]);

        //endregion


        //region roles
        // Crear rol admin de forma segura
        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web'
        ]);
        //endregion

        //region asignaciones
        // Asignar permiso al rol admin
        $adminRole->givePermissionTo($permInventario);
        $adminRole->givePermissionTo($permReportes);
        $adminRole->givePermissionTo($permProductoCreate);
        $adminRole->givePermissionTo($permProductoEdit);
        $adminRole->givePermissionTo($permProductoDelete);
        $adminRole->givePermissionTo($permActions);
        $adminRole->givePermissionTo($permVentasRevocar);
        //endregion

        //region usuarios
        // Crear usuario admin
        $adminUser = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Admin',
                'password' => Hash::make('admin2025'),
            ]
        );

        // Asignar rol admin al usuario admin
        $adminUser->assignRole($adminRole);


        // Crear usuario ventas (sin rol por ahora)
        User::firstOrCreate(
            ['username' => 'ventas'],
            [
                'name' => 'ventas',
                'password' => Hash::make('ventas25'),
            ]
        );
        //endregion
    }
}

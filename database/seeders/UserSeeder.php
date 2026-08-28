<?php

namespace Database\Seeders;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Ported from USERS in stock-data.js. Note the seed data deliberately gives
     * each user a permission set that is a subset of their role's defaults —
     * this is intentional in the prototype: permissions belong to the user,
     * not just the role. Dev password for every seeded account is "password".
     */
    public function run(): void
    {
        $rows = [
            [
                'name' => 'สุหรรษา พลยศ',
                'email' => 'suhansa@gmail.com',
                'role' => UserRole::Owner,
                'permissions' => [Permission::ViewReports, Permission::StockMovements, Permission::EditProducts, Permission::ManageUsers],
            ],
            [
                'name' => 'ปรียา ศรีทอง',
                'email' => 'preeya@rungrueang.co.th',
                'role' => UserRole::StoreManager,
                'permissions' => [Permission::ViewReports, Permission::StockMovements, Permission::EditProducts],
            ],
            [
                'name' => 'ธนากร ใจดี',
                'email' => 'thanakorn@rungrueang.co.th',
                'role' => UserRole::WarehouseStaff,
                'permissions' => [Permission::StockMovements],
            ],
            [
                'name' => 'มาลี พงษ์ศรี',
                'email' => 'malee@rungrueang.co.th',
                'role' => UserRole::SalesStaff,
                'permissions' => [Permission::StockMovements],
            ],
            [
                'name' => 'ผู้ทดสอบระบบ (QA)',
                'email' => 'tester@rungrueang.co.th',
                'role' => UserRole::Tester,
                'permissions' => [Permission::ViewReports, Permission::StockMovements, Permission::EditProducts, Permission::StockCount, Permission::OnlineSales],
            ],
        ];

        foreach ($rows as $row) {
            User::updateOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'password' => Hash::make('password'),
                    'role' => $row['role'],
                    'permissions' => array_map(fn (Permission $p) => $p->value, $row['permissions']),
                    'active' => true,
                ]
            );
        }
    }
}

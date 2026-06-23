<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            // ==================== ADMIN ====================
            [
                'full_name'        => 'Nguyễn Đình Tú',
                'phone_number'     => '0336620188',
                'email'            => 'admin@cafeshop.vn',
                'password'         => Hash::make('Admin@123'),
                'role'             => 'ADMIN',
                'gender'           => 'Nam',
                'date_of_birth'    => '1990-05-15',
                'reward_points'    => 0,
                'status'           => 'ACTIVE',
                'is_email_verified'=> true,
                'google_id'        => null,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],

            // ==================== STAFF (Nhân viên) ====================
            [
                'full_name'        => 'Nguyễn Lý Đoàn Lộc',
                'phone_number'     => '0902000001',
                'email'            => 'staff1@cafeshop.vn',
                'password'         => Hash::make('Staff@123'),
                'role'             => 'STAFF',
                'gender'           => 'Nam',
                'date_of_birth'    => '1998-09-20',
                'reward_points'    => 0,
                'status'           => 'ACTIVE',
                'is_email_verified'=> true,
                'google_id'        => null,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'full_name'        => 'Trần Nhật Duy',
                'phone_number'     => '0902000002',
                'email'            => 'staff2@cafeshop.vn',
                'password'         => Hash::make('Staff@123'),
                'role'             => 'STAFF',
                'gender'           => 'Nam',
                'date_of_birth'    => '2006-03-10',
                'reward_points'    => 0,
                'status'           => 'ACTIVE',
                'is_email_verified'=> true,
                'google_id'        => null,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],

            // ==================== BARISTA (Pha chế) ====================
            [
                'full_name'        => 'Lê Hà Như Ý',
                'phone_number'     => '0903000001',
                'email'            => 'barista1@cafeshop.vn',
                'password'         => Hash::make('Barista@123'),
                'role'             => 'BARISTA',
                'gender'           => 'Nữ',
                'date_of_birth'    => '2006-11-25',
                'reward_points'    => 0,
                'status'           => 'ACTIVE',
                'is_email_verified'=> true,
                'google_id'        => null,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'full_name'        => 'Nguyễn Phi Trường',
                'phone_number'     => '0903000002',
                'email'            => 'barista2@cafeshop.vn',
                'password'         => Hash::make('Barista@123'),
                'role'             => 'BARISTA',
                'gender'           => 'Nữ',
                'date_of_birth'    => '1999-07-14',
                'reward_points'    => 0,
                'status'           => 'ACTIVE',
                'is_email_verified'=> true,
                'google_id'        => null,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],

            // ==================== CUSTOMER (Khách hàng) ====================
            [
                'full_name'        => 'Hồ Văn Phi',
                'phone_number'     => '0904000001',
                'email'            => 'customer1@gmail.com',
                'password'         => Hash::make('Customer@123'),
                'role'             => 'CUSTOMER',
                'gender'           => 'Nam',
                'date_of_birth'    => '2001-01-30',
                'reward_points'    => 150,
                'status'           => 'ACTIVE',
                'is_email_verified'=> true,
                'google_id'        => null,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'full_name'        => 'Trần Dương Luân',
                'phone_number'     => '0904000002',
                'email'            => 'customer2@gmail.com',
                'password'         => Hash::make('Customer@123'),
                'role'             => 'CUSTOMER',
                'gender'           => 'Nữ',
                'date_of_birth'    => '2002-09-05',
                'reward_points'    => 80,
                'status'           => 'ACTIVE',
                'is_email_verified'=> true,
                'google_id'        => null,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'full_name'        => 'Doãn Văn Đại',
                'phone_number'     => '0904000003',
                'email'            => 'customer3@gmail.com',
                'password'         => Hash::make('Customer@123'),
                'role'             => 'CUSTOMER',
                'gender'           => 'Nam',
                'date_of_birth'    => '1995-04-18',
                'reward_points'    => 0,
                'status'           => 'INACTIVE', // tài khoản chưa kích hoạt — để test case này
                'is_email_verified'=> false,
                'google_id'        => null,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
        ];

        DB::table('users')->insert($users);
    }
}
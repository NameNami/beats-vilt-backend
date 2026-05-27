<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Programme;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Admin ────────────────────────────────────────────
        User::updateOrCreate(
            ['email' => 'admin@beats.namix.my'],
            [
                'name'       => 'System Admin',
                'username'   => 'admin',
                'password'   => Hash::make('password'),
                'role'       => 'admin',
                'is_active'  => true,
            ]
        );

        // ─── Lecturers ────────────────────────────────────────
        $lecturers = [
            ['name' => 'Dr. Azrai Abdul Aziz',   'username' => 'azrai'],
            ['name' => 'Mr. Hafiz Rahman',        'username' => 'hafiz'],
            ['name' => 'Ms. Suraya Kamaruddin',   'username' => 'suraya'],
        ];

        foreach ($lecturers as $lecturer) {
            User::updateOrCreate(
                ['email' => $lecturer['username'] . '@beats.namix.my'],
                [
                    'name'      => $lecturer['name'],
                    'username'  => $lecturer['username'],
                    'password'  => Hash::make('password'),
                    'role'      => 'lecturer',
                    'is_active' => true,
                ]
            );
        }

        // ─── Students ─────────────────────────────────────────
        $dit = Programme::where('code', 'DIT')->first();
        $dim = Programme::where('code', 'DIM')->first();

        $students = [
            ['name' => 'Muhammad Najmi',     'username' => 'najmi',     'student_id' => '52101324169', 'programme' => $dit],
            ['name' => 'Muhammad Khaizuran', 'username' => 'khaizuran', 'student_id' => '52101324249', 'programme' => $dit],
            ['name' => 'Muhammad Haikal',    'username' => 'haikal',    'student_id' => '52101324316', 'programme' => $dit],
            ['name' => 'Amirul Haziq',       'username' => 'amirul',    'student_id' => '52101324101', 'programme' => $dit],
            ['name' => 'Nurul Ain',          'username' => 'ain',       'student_id' => '52101324102', 'programme' => $dit],
            ['name' => 'Syafiq Danial',      'username' => 'syafiq',    'student_id' => '52101324103', 'programme' => $dit],
            ['name' => 'Izzatul Husna',      'username' => 'izzatul',   'student_id' => '52101324104', 'programme' => $dit],
            ['name' => 'Hazwan Faris',       'username' => 'hazwan',    'student_id' => '52101324105', 'programme' => $dit],
            ['name' => 'Liyana Nabilah',     'username' => 'liyana',    'student_id' => '52101324106', 'programme' => $dit],
            ['name' => 'Arif Danial',        'username' => 'arif',      'student_id' => '52101324107', 'programme' => $dit],
            ['name' => 'Fatin Nadhirah',     'username' => 'fatin',     'student_id' => '52101324108', 'programme' => $dit],
            ['name' => 'Zulhilmi Azman',     'username' => 'zulhilmi',  'student_id' => '52101324109', 'programme' => $dit],
            ['name' => 'Nur Hidayah',        'username' => 'hidayah',   'student_id' => '52101324110', 'programme' => $dit],
            ['name' => 'Aidil Fitri',        'username' => 'aidil',     'student_id' => '52101324111', 'programme' => $dit],
            ['name' => 'Safwan Hakim',       'username' => 'safwan',    'student_id' => '52101324112', 'programme' => $dit],
            ['name' => 'Syazwan Yusof',      'username' => 'syazwan',   'student_id' => '52101324113', 'programme' => $dit],
            ['name' => 'Nurul Izzah',        'username' => 'izzah',     'student_id' => '52101324114', 'programme' => $dit],
            ['name' => 'Ahmad Fauzi',        'username' => 'fauzi',     'student_id' => '52101324115', 'programme' => $dit],
            ['name' => 'Siti Khadijah',      'username' => 'khadijah',  'student_id' => '52101324116', 'programme' => $dit],
            ['name' => 'Alif Iman',          'username' => 'alif',      'student_id' => '52101324201', 'programme' => $dim],
            ['name' => 'Darwisyah Husna',    'username' => 'darwisyah', 'student_id' => '52101324202', 'programme' => $dim],
            ['name' => 'Farhan Asyraf',      'username' => 'farhan',    'student_id' => '52101324203', 'programme' => $dim],
            ['name' => 'Hanis Izzati',       'username' => 'hanis',     'student_id' => '52101324204', 'programme' => $dim],
            ['name' => 'Irfan Hakimi',       'username' => 'irfan',     'student_id' => '52101324205', 'programme' => $dim],
            ['name' => 'Balqis Sofia',       'username' => 'balqis',    'student_id' => '52101324206', 'programme' => $dim],
            ['name' => 'Haris Irfan',        'username' => 'haris',     'student_id' => '52101324207', 'programme' => $dim],
            ['name' => 'Anis Farhana',       'username' => 'anis',      'student_id' => '52101324208', 'programme' => $dim],
            ['name' => 'Muhamad Akmal',      'username' => 'akmal',     'student_id' => '52101324301', 'programme' => $dit],
            ['name' => 'Nur Syahirah',       'username' => 'syahirah',  'student_id' => '52101324302', 'programme' => $dit],
            ['name' => 'Adam Harith',        'username' => 'adam',      'student_id' => '52101324303', 'programme' => $dit],
            ['name' => 'Siti Aminah',        'username' => 'aminah',    'student_id' => '52101324304', 'programme' => $dit],
            ['name' => 'Wan Muhammad',       'username' => 'wan',       'student_id' => '52101324305', 'programme' => $dit],
            ['name' => 'Nurul Atikah',       'username' => 'atikah',    'student_id' => '52101324306', 'programme' => $dim],
            ['name' => 'Muhammad Danish',    'username' => 'danish',    'student_id' => '52101324307', 'programme' => $dim],
            ['name' => 'Puteri Sarah',       'username' => 'sarah',     'student_id' => '52101324308', 'programme' => $dim],
            ['name' => 'Megat Aris',         'username' => 'megat',     'student_id' => '52101324309', 'programme' => $dim],
            ['name' => 'Nur Fatimah',        'username' => 'fatimah',   'student_id' => '52101324310', 'programme' => $dim],
        ];

        foreach ($students as $student) {
            User::updateOrCreate(
                ['email' => $student['username'] . '@student.beats.namix.my'],
                [
                    'name'           => $student['name'],
                    'username'       => $student['username'],
                    'password'       => Hash::make('password'),
                    'role'           => 'student',
                    'student_id'     => $student['student_id'],
                    'programme_id'   => $student['programme']->id,
                    'is_active'      => true,
                ]
            );
        }
    }
}

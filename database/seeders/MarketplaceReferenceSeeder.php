<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MarketplaceReferenceSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        DB::table('categories')->updateOrInsert(['slug' => 'education'], ['name' => 'Education', 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('categories')->updateOrInsert(['slug' => 'electronics'], ['name' => 'Electronics', 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('categories')->updateOrInsert(['slug' => 'furniture'], ['name' => 'Furniture', 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('categories')->updateOrInsert(['slug' => 'fashion'], ['name' => 'Fashion', 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('categories')->updateOrInsert(['slug' => 'hobbies'], ['name' => 'Hobbies', 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('categories')->updateOrInsert(['slug' => 'self-care'], ['name' => 'Self-Care', 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('categories')->updateOrInsert(['slug' => 'others'], ['name' => 'Others', 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('meetup_points')->updateOrInsert(['slug' => 'balairung'], ['short_name' => 'Balairung', 'name' => 'Balairung UGM', 'kind' => 'public', 'map_x' => 50, 'map_y' => 18, 'tip' => 'Meet in front of the main building, near the stairs.', 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('meetup_points')->updateOrInsert(['slug' => 'perpustakaan'], ['short_name' => 'Perpustakaan', 'name' => 'Perpustakaan Pusat UGM', 'kind' => 'public', 'map_x' => 40, 'map_y' => 47, 'tip' => 'Meet at the main entrance lobby.', 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('meetup_points')->updateOrInsert(['slug' => 'student-center'], ['short_name' => 'Student Center', 'name' => 'Student Center UGM', 'kind' => 'public', 'map_x' => 61, 'map_y' => 47, 'tip' => 'Busy with student activities most days.', 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('meetup_points')->updateOrInsert(['slug' => 'boulevard'], ['short_name' => 'Boulevard', 'name' => 'Boulevard UGM', 'kind' => 'public', 'map_x' => 50, 'map_y' => 70, 'tip' => 'Open area along the boulevard; pick a spot near the benches.', 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('meetup_points')->updateOrInsert(['slug' => 'gsp'], ['short_name' => 'GSP', 'name' => 'Grha Sabha Pramana UGM', 'kind' => 'public', 'map_x' => 56, 'map_y' => 30, 'tip' => 'Large open plaza in front of the building.', 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('meetup_points')->updateOrInsert(['slug' => 'masjid'], ['short_name' => 'Masjid Kampus', 'name' => 'Masjid Kampus UGM', 'kind' => 'public', 'map_x' => 86, 'map_y' => 70, 'tip' => 'Meet at the front yard, outside prayer times.', 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('meetup_points')->updateOrInsert(['slug' => 'biologi'], ['short_name' => 'Biologi', 'name' => 'Fakultas Biologi UGM', 'kind' => 'faculty', 'map_x' => 31, 'map_y' => 31, 'tip' => 'Meet at the faculty\'s main lobby or canteen area during the day.', 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('faculties')->updateOrInsert(['code' => 'Biologi'], ['name' => 'Biologi', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('meetup_points')->updateOrInsert(['slug' => 'feb'], ['short_name' => 'FEB', 'name' => 'Fakultas Ekonomika dan Bisnis UGM', 'kind' => 'faculty', 'map_x' => 68, 'map_y' => 63, 'tip' => 'Meet at the faculty\'s main lobby or canteen area during the day.', 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('faculties')->updateOrInsert(['code' => 'FEB'], ['name' => 'FEB', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('meetup_points')->updateOrInsert(['slug' => 'farmasi'], ['short_name' => 'Farmasi', 'name' => 'Fakultas Farmasi UGM', 'kind' => 'faculty', 'map_x' => 20, 'map_y' => 46, 'tip' => 'Meet at the faculty\'s main lobby or canteen area during the day.', 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('faculties')->updateOrInsert(['code' => 'Farmasi'], ['name' => 'Farmasi', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('meetup_points')->updateOrInsert(['slug' => 'filsafat'], ['short_name' => 'Filsafat', 'name' => 'Fakultas Filsafat UGM', 'kind' => 'faculty', 'map_x' => 82, 'map_y' => 54, 'tip' => 'Meet at the faculty\'s main lobby or canteen area during the day.', 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('faculties')->updateOrInsert(['code' => 'Filsafat'], ['name' => 'Filsafat', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('meetup_points')->updateOrInsert(['slug' => 'geografi'], ['short_name' => 'Geografi', 'name' => 'Fakultas Geografi UGM', 'kind' => 'faculty', 'map_x' => 33, 'map_y' => 62, 'tip' => 'Meet at the faculty\'s main lobby or canteen area during the day.', 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('faculties')->updateOrInsert(['code' => 'Geografi'], ['name' => 'Geografi', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('meetup_points')->updateOrInsert(['slug' => 'fh'], ['short_name' => 'FH', 'name' => 'Fakultas Hukum UGM', 'kind' => 'faculty', 'map_x' => 74, 'map_y' => 40, 'tip' => 'Meet at the faculty\'s main lobby or canteen area during the day.', 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('faculties')->updateOrInsert(['code' => 'FH'], ['name' => 'FH', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('meetup_points')->updateOrInsert(['slug' => 'fib'], ['short_name' => 'FIB', 'name' => 'Fakultas Ilmu Budaya UGM', 'kind' => 'faculty', 'map_x' => 88, 'map_y' => 40, 'tip' => 'Meet at the faculty\'s main lobby or canteen area during the day.', 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('faculties')->updateOrInsert(['code' => 'FIB'], ['name' => 'FIB', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('meetup_points')->updateOrInsert(['slug' => 'fisipol'], ['short_name' => 'FISIPOL', 'name' => 'Fakultas Ilmu Sosial dan Ilmu Politik UGM', 'kind' => 'faculty', 'map_x' => 70, 'map_y' => 22, 'tip' => 'Meet at the faculty\'s main lobby or canteen area during the day.', 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('faculties')->updateOrInsert(['code' => 'FISIPOL'], ['name' => 'FISIPOL', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('meetup_points')->updateOrInsert(['slug' => 'fk-kmk'], ['short_name' => 'FK-KMK', 'name' => 'Fakultas Kedokteran, Kesehatan Masyarakat, dan Keperawatan UGM', 'kind' => 'faculty', 'map_x' => 10, 'map_y' => 30, 'tip' => 'Meet at the faculty\'s main lobby or canteen area during the day.', 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('faculties')->updateOrInsert(['code' => 'FK-KMK'], ['name' => 'FK-KMK', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('meetup_points')->updateOrInsert(['slug' => 'fkg'], ['short_name' => 'FKG', 'name' => 'Fakultas Kedokteran Gigi UGM', 'kind' => 'faculty', 'map_x' => 16, 'map_y' => 16, 'tip' => 'Meet at the faculty\'s main lobby or canteen area during the day.', 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('faculties')->updateOrInsert(['code' => 'FKG'], ['name' => 'FKG', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('meetup_points')->updateOrInsert(['slug' => 'fkh'], ['short_name' => 'FKH', 'name' => 'Fakultas Kedokteran Hewan UGM', 'kind' => 'faculty', 'map_x' => 24, 'map_y' => 76, 'tip' => 'Meet at the faculty\'s main lobby or canteen area during the day.', 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('faculties')->updateOrInsert(['code' => 'FKH'], ['name' => 'FKH', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('meetup_points')->updateOrInsert(['slug' => 'fkt'], ['short_name' => 'FKT', 'name' => 'Fakultas Kehutanan UGM', 'kind' => 'faculty', 'map_x' => 14, 'map_y' => 64, 'tip' => 'Meet at the faculty\'s main lobby or canteen area during the day.', 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('faculties')->updateOrInsert(['code' => 'FKT'], ['name' => 'FKT', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('meetup_points')->updateOrInsert(['slug' => 'fmipa'], ['short_name' => 'FMIPA', 'name' => 'Fakultas Matematika dan Ilmu Pengetahuan Alam UGM', 'kind' => 'faculty', 'map_x' => 36, 'map_y' => 16, 'tip' => 'Meet at the faculty\'s main lobby or canteen area during the day.', 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('faculties')->updateOrInsert(['code' => 'FMIPA'], ['name' => 'FMIPA', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('meetup_points')->updateOrInsert(['slug' => 'faperta'], ['short_name' => 'FAPERTA', 'name' => 'Fakultas Pertanian UGM', 'kind' => 'faculty', 'map_x' => 40, 'map_y' => 82, 'tip' => 'Meet at the faculty\'s main lobby or canteen area during the day.', 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('faculties')->updateOrInsert(['code' => 'FAPERTA'], ['name' => 'FAPERTA', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('meetup_points')->updateOrInsert(['slug' => 'fapet'], ['short_name' => 'FAPET', 'name' => 'Fakultas Peternakan UGM', 'kind' => 'faculty', 'map_x' => 12, 'map_y' => 86, 'tip' => 'Meet at the faculty\'s main lobby or canteen area during the day.', 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('faculties')->updateOrInsert(['code' => 'FAPET'], ['name' => 'FAPET', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('meetup_points')->updateOrInsert(['slug' => 'psikologi'], ['short_name' => 'Psikologi', 'name' => 'Fakultas Psikologi UGM', 'kind' => 'faculty', 'map_x' => 84, 'map_y' => 24, 'tip' => 'Meet at the faculty\'s main lobby or canteen area during the day.', 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('faculties')->updateOrInsert(['code' => 'Psikologi'], ['name' => 'Psikologi', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('meetup_points')->updateOrInsert(['slug' => 'ft'], ['short_name' => 'FT', 'name' => 'Fakultas Teknik UGM', 'kind' => 'faculty', 'map_x' => 62, 'map_y' => 84, 'tip' => 'Meet at the faculty\'s main lobby or canteen area during the day.', 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('faculties')->updateOrInsert(['code' => 'FT'], ['name' => 'FT', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('meetup_points')->updateOrInsert(['slug' => 'ftp'], ['short_name' => 'FTP', 'name' => 'Fakultas Teknologi Pertanian UGM', 'kind' => 'faculty', 'map_x' => 30, 'map_y' => 90, 'tip' => 'Meet at the faculty\'s main lobby or canteen area during the day.', 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('faculties')->updateOrInsert(['code' => 'FTP'], ['name' => 'FTP', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('meetup_points')->updateOrInsert(['slug' => 'sv'], ['short_name' => 'SV', 'name' => 'Sekolah Vokasi UGM', 'kind' => 'faculty', 'map_x' => 90, 'map_y' => 86, 'tip' => 'Meet at the faculty\'s main lobby or canteen area during the day.', 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('faculties')->updateOrInsert(['code' => 'SV'], ['name' => 'SV', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('meetup_points')->updateOrInsert(['slug' => 'sps'], ['short_name' => 'SPs', 'name' => 'Sekolah Pascasarjana UGM', 'kind' => 'faculty', 'map_x' => 78, 'map_y' => 10, 'tip' => 'Meet at the faculty\'s main lobby or canteen area during the day.', 'active' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('faculties')->updateOrInsert(['code' => 'SPs'], ['name' => 'SPs', 'created_at' => $now, 'updated_at' => $now]);
    }
}

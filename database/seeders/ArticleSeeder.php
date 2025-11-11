<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $author = User::first() ?? User::factory()->create([
            'name' => 'Admin',
            'email' => 'root@gmail.com',
        ]);

        $categories = [
            'Persyaratan Dasar',
            'Proses Bisnis',
            'OSS RBA',
            'Validasi Dokumen',
            'Pelaporan Usaha',
            'Analisis Risiko',
            'Perizinan Usaha',
        ];

        $tagPool = [
            'Tag 1',
            'Tag 2',
            'Tag 3',
            'Tag 4',
            'Tag 5',
            'Tag 6',
            'Tag 7',
            'Tag 8',
            'Tag 9',
            'Tag 10',
        ];

        $titles = [
            'Panduan Penggunaan OSS RBA',
            'Prosedur Pelaporan Kegiatan Usaha',
            'Manajemen Risiko Perizinan Berusaha',
            'Langkah Efektif Validasi Dokumen',
            'Bisnis Proses Persyaratan Dasar',
            'Strategi Optimalisasi OSS',
            'Implementasi RBA dalam Proses Izin',
            'Digitalisasi Perizinan Usaha',
            'Cara Efektif Monitoring Pelaporan Usaha',
            'Langkah-langkah Analisis Risiko',
            'Optimasi Validasi Dokumen',
            'Kebijakan Baru Perizinan Terpadu',
            'Tata Kelola Data Perizinan',
            'Best Practice Proses RBA',
            'Reformasi Birokrasi di OSS',
            'Standarisasi Formulir Perizinan',
            'Efisiensi Layanan Online OSS',
            'Penerapan OSS di Sektor Industri',
            'Sinkronisasi Data NIB',
            'Evaluasi Layanan OSS RBA',
            'Inovasi Digital pada OSS',
            'Pemantauan Kepatuhan Pelaku Usaha',
            'Pencegahan Kesalahan Validasi',
            'Automasi Proses Perizinan',
            'Tantangan Implementasi RBA',
            'Analisis Tren OSS Nasional',
            'Panduan Pengajuan Perubahan Data Usaha',
            'Manfaat Integrasi OSS Daerah',
            'Optimalisasi Database Perizinan',
            'Roadmap Transformasi OSS 2025',
        ];

        foreach ($titles as $title) {
            $category = Arr::random($categories);
            $tags = Arr::random($tagPool, rand(2, 4));

            Article::create([
                'title' => $title,
                'excerpt' => 'Artikel ini membahas topik "' . $title . '" secara ringkas dan praktis untuk pelaku usaha.',
                'category' => $category,
                'tags' => $tags,
                'views' => rand(50, 500),
                'likes' => rand(5, 50),
                'author_id' => $author->id,
            ]);
        }
    }
}

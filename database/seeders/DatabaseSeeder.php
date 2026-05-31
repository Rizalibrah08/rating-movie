<?php

namespace Database\Seeders;

use App\Models\Genre;
use App\Models\Movie;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    private string $heroBackdrop = '/the-boys.jpg';

    private array $movies = [
        [
            'title' => 'Cars',
            'year' => 2006,
            'director' => 'John Lasseter',
            'duration' => 117,
            'poster' => '/cars.jpg',
            'synopsis' => 'Lightning McQueen, mobil balap arogan yang terobsesi menjadi juara Piston Cup, terdampar di kota kecil Radiator Springs. Di sana ia belajar bahwa hidup bukan hanya soal kecepatan, tapi juga tentang persahabatan dan menghargai perjalanan.',
            'genres' => ['Animasi', 'Komedi', 'Petualangan'],
        ],
        [
            'title' => 'Despicable Me 3',
            'year' => 2017,
            'director' => 'Pierre Coffin',
            'duration' => 90,
            'poster' => '/despicable-me-3.jpg',
            'synopsis' => 'Gru dipecat dari Anti-Villain League dan bertemu saudara kembarnya, Dru, yang ingin menjadi penjahat super. Sementara itu, mantan bintang cilik Balthazar Bratt berambisi membalas dendam pada dunia yang melupakannya.',
            'genres' => ['Animasi', 'Komedi'],
        ],
        [
            'title' => 'Home Alone',
            'year' => 1990,
            'director' => 'Chris Columbus',
            'duration' => 103,
            'poster' => '/home-alone.jpg',
            'synopsis' => 'Kevin McCallister, bocah 8 tahun yang secara tidak sengaja ditinggal keluarganya saat liburan Natal, harus mempertahankan rumahnya dari dua pencuri bodoh menggunakan jebakan-jebakan kreatif buatannya sendiri.',
            'genres' => ['Komedi', 'Drama'],
        ],
        [
            'title' => 'Kung Fu Panda',
            'year' => 2008,
            'director' => 'Mark Osborne',
            'duration' => 92,
            'poster' => '/kung-fu-panda.jpg',
            'synopsis' => 'Po, seekor panda gemuk penjual mie yang bermimpi menjadi ahli kung fu, secara mengejutkan terpilih sebagai Dragon Warrior. Ia harus berlatih keras untuk mengalahkan Tai Lung, penjahat yang mengancam Lembah Kedamaian.',
            'genres' => ['Animasi', 'Aksi', 'Komedi'],
        ],
        [
            'title' => 'Ratatouille',
            'year' => 2007,
            'director' => 'Brad Bird',
            'duration' => 111,
            'poster' => '/ratatouille.jpg',
            'synopsis' => 'Remy, seekor tikus dengan indra penciuman luar biasa dan passion memasak, bermimpi menjadi chef di Paris. Ia berkolaborasi dengan Linguini, pemuda canggung yang bekerja di restoran mendiang Chef Gusteau.',
            'genres' => ['Animasi', 'Komedi', 'Drama'],
        ],
        [
            'title' => 'Up',
            'year' => 2009,
            'director' => 'Pete Docter',
            'duration' => 96,
            'poster' => '/up.jpg',
            'synopsis' => 'Carl Fredricksen, kakek 78 tahun yang kesepian, menerbangkan rumahnya dengan ribuan balon menuju Paradise Falls untuk memenuhi janji pada mendiang istrinya. Tanpa sengaja, bocah pramuka bernama Russell ikut dalam petualangannya.',
            'genres' => ['Animasi', 'Petualangan', 'Drama'],
        ],
        [
            'title' => 'World War Z',
            'year' => 2013,
            'director' => 'Marc Forster',
            'duration' => 116,
            'poster' => '/world-war-z.jpg',
            'synopsis' => 'Gerry Lane, mantan investigator PBB, harus melintasi dunia yang sedang dilanda pandemi zombie untuk menemukan sumber wabah dan cara menghentikannya sebelum peradaban manusia musnah sepenuhnya.',
            'genres' => ['Aksi', 'Thriller'],
        ],
    ];

    // Ulasan natural berbahasa Indonesia untuk setiap film
    private array $reviews = [
        'Cars' => [
            ['rating' => 82, 'body' => 'Film ini ngajarin banyak hal soal hidup yang nggak cuma soal menang. Animasinya keren banget buat zamannya, dan karakter-karakternya memorable. Lightning McQueen awalnya nyebelin tapi perkembangannya bikin kita ikut seneng.'],
            ['rating' => 75, 'body' => 'Seru sih buat ditonton bareng keluarga. Ceritanya sederhana tapi pesannya dapet. Cuma kadang agak lambat di bagian tengah, tapi endingnya memuaskan.'],
            ['rating' => 68, 'body' => 'Oke lah buat film anak-anak. Tapi kalau dibanding film Pixar lain kayak Toy Story atau Finding Nemo, ini agak kurang greget menurut gue. Tetep enjoyable sih.'],
        ],
        'Despicable Me 3' => [
            ['rating' => 60, 'body' => 'Minions-nya tetep lucu, tapi ceritanya udah mulai repetitif. Villain-nya kurang menakutkan dibanding film pertama. Anak-anak pasti suka, tapi buat orang dewasa agak biasa aja.'],
            ['rating' => 55, 'body' => 'Nggak sebaik film pertamanya. Karakter Dru agak annoying dan plotnya predictable. Tapi ya tetep menghibur lah buat nonton santai weekend.'],
            ['rating' => 72, 'body' => 'Gue suka chemistry antara Gru dan Dru. Balthazar Bratt juga villain yang unik dengan tema 80s-nya. Lumayan lah buat franchise yang udah film ketiga.'],
        ],
        'Home Alone' => [
            ['rating' => 90, 'body' => 'Klasik banget! Udah nonton berkali-kali dari kecil dan tetep ngakak. Kevin itu genius, jebakan-jebakannya kreatif parah. Film Natal terbaik sepanjang masa menurut gue.'],
            ['rating' => 85, 'body' => 'Film yang nggak pernah bosen ditonton ulang tiap Natal. Macaulay Culkin aktingnya natural banget sebagai anak kecil yang resourceful. Komedinya timeless.'],
            ['rating' => 78, 'body' => 'Lucu dan heartwarming. Emang agak unrealistic sih kalau dipikir-pikir, tapi namanya juga film komedi. Pesannya tentang keluarga tetep nyentuh.'],
        ],
        'Kung Fu Panda' => [
            ['rating' => 88, 'body' => 'Salah satu film animasi terbaik yang pernah gue tonton! Aksi kung fu-nya epic, humornya dapet, dan pesannya dalam banget. "There is no secret ingredient" itu quote yang selalu gue inget.'],
            ['rating' => 80, 'body' => 'Jack Black perfect banget jadi Po. Film ini buktiin kalau DreamWorks bisa bikin film yang setara sama Pixar. Visualnya stunning dan ceritanya engaging dari awal sampai akhir.'],
            ['rating' => 76, 'body' => 'Keren! Tai Lung itu salah satu villain animasi paling badass. Choreography fight scene-nya detail banget. Cuma wish ada lebih banyak screen time buat Furious Five.'],
        ],
        'Ratatouille' => [
            ['rating' => 92, 'body' => 'Masterpiece dari Pixar. Film ini bikin gue pengen belajar masak. Pesannya bahwa siapapun bisa jadi hebat kalau punya passion itu inspiring banget. Animasi makanannya bikin laper!'],
            ['rating' => 87, 'body' => 'Ceritanya unik dan eksekusinya sempurna. Remy itu karakter yang relatable — punya mimpi yang dianggap mustahil sama orang lain tapi tetep berjuang. Scene review Anton Ego di akhir itu powerful banget.'],
            ['rating' => 83, 'body' => 'Film yang underrated menurut gue. Banyak orang skip karena premisnya "tikus masak" kedengeran aneh, tapi ini salah satu storytelling terbaik Pixar. Highly recommended.'],
        ],
        'Up' => [
            ['rating' => 95, 'body' => 'Opening sequence-nya aja udah bikin nangis. Film ini buktiin kalau Pixar bisa bikin cerita yang menyentuh hati tanpa dialog panjang. Carl dan Ellie itu love story paling indah di dunia animasi.'],
            ['rating' => 88, 'body' => 'Petualangannya seru, tapi yang bikin film ini spesial adalah emosi di baliknya. Tentang letting go, moving on, dan menemukan keluarga baru. Russell dan Dug itu duo yang wholesome banget.'],
            ['rating' => 79, 'body' => 'Bagus banget di awal dan akhir, tapi bagian tengah petualangannya agak standar buat film Pixar. Tetep film yang bagus sih, cuma ekspektasi gue tinggi karena opening-nya legendary.'],
        ],
        'World War Z' => [
            ['rating' => 70, 'body' => 'Beda banget sama bukunya, tapi sebagai film zombie standalone ini cukup seru. Brad Pitt solid, pace-nya cepet, dan scale zombie apocalypse-nya epic. Cuma endingnya agak anticlimactic.'],
            ['rating' => 65, 'body' => 'Lumayan lah buat genre zombie. Nggak se-gory film zombie lain karena PG-13, tapi tension-nya tetep kerasa. Scene di pesawat dan di Israel itu intense banget.'],
            ['rating' => 58, 'body' => 'Sebagai adaptasi novel, ini mengecewakan. Tapi kalau diliat sebagai blockbuster action biasa, ya oke lah. CGI zombie-nya kadang keliatan fake tapi overall watchable.'],
        ],
    ];

    public function run(): void
    {
        // Admin
        User::factory()->create([
            'name' => 'Admin Utama',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);

        // Members
        $members = collect([
            ['name' => 'Rizky Pratama', 'email' => 'rizky@example.com'],
            ['name' => 'Siti Nurhaliza', 'email' => 'siti@example.com'],
            ['name' => 'Budi Santoso', 'email' => 'budi@example.com'],
        ])->map(fn ($data) => User::factory()->create([
            ...$data,
            'password' => Hash::make('password'),
            'role' => User::ROLE_USER,
            'email_verified_at' => now(),
        ]));

        // Genre
        $genreNames = ['Drama', 'Horor', 'Romantis', 'Aksi', 'Komedi', 'Thriller', 'Animasi', 'Dokumenter', 'Petualangan', 'Fantasi'];
        $genres = collect($genreNames)->mapWithKeys(fn ($name) => [
            $name => Genre::create(['name' => $name, 'slug' => Str::slug($name)]),
        ]);

        // Film + ulasan
        foreach ($this->movies as $data) {
            $movie = Movie::create([
                'title' => $data['title'],
                'slug' => Str::slug($data['title']),
                'synopsis' => $data['synopsis'],
                'year' => $data['year'],
                'duration_min' => $data['duration'],
                'director' => $data['director'],
                'poster_path' => null,
                'poster_url' => $data['poster'],
                'backdrop_path' => null,
                'backdrop_url' => $this->heroBackdrop,
            ]);

            $genreIds = collect($data['genres'])->map(fn ($g) => $genres[$g]->id)->all();
            $movie->genres()->attach($genreIds);

            // Tambah ulasan
            if (isset($this->reviews[$data['title']])) {
                foreach ($this->reviews[$data['title']] as $i => $reviewData) {
                    Review::create([
                        'user_id' => $members[$i % $members->count()]->id,
                        'movie_id' => $movie->id,
                        'rating' => $reviewData['rating'],
                        'body' => $reviewData['body'],
                        'status' => Review::STATUS_PUBLISHED,
                        'ip' => '127.0.0.1',
                    ]);
                }
            }
        }

        $this->call(BlockedKeywordSeeder::class);
    }
}

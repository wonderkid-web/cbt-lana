-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 10, 2026 at 06:37 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_cbt_binjai`
--

-- --------------------------------------------------------

--
-- Table structure for table `absensi_hari_ini`
--

CREATE TABLE `absensi_hari_ini` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `sudah_absen` tinyint(1) DEFAULT 1,
  `tanggal` date NOT NULL,
  `waktu_absen` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `absensi_hari_ini`
--

INSERT INTO `absensi_hari_ini` (`id`, `siswa_id`, `sudah_absen`, `tanggal`, `waktu_absen`) VALUES
(14, 2, 1, '2026-05-09', '2026-05-09 11:10:48'),
(15, 4, 1, '2026-05-09', '2026-05-09 11:20:54'),
(16, 5, 1, '2026-05-09', '2026-05-09 12:46:40');

-- --------------------------------------------------------

--
-- Table structure for table `bank_soal`
--

CREATE TABLE `bank_soal` (
  `id_soal` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `id_mapel` int(11) DEFAULT NULL COMMENT 'ID Mata Pelajaran',
  `id_kelas` int(11) DEFAULT NULL,
  `pertanyaan` text NOT NULL,
  `tipe_soal` enum('pg','bs','essay') NOT NULL DEFAULT 'pg',
  `opsi_a` text DEFAULT NULL,
  `opsi_b` text DEFAULT NULL,
  `opsi_c` text DEFAULT NULL,
  `opsi_d` text DEFAULT NULL,
  `opsi_e` text DEFAULT NULL,
  `kunci_jawaban` varchar(5) DEFAULT NULL,
  `status` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `angkatan` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bank_soal`
--

INSERT INTO `bank_soal` (`id_soal`, `id_user`, `id_mapel`, `id_kelas`, `pertanyaan`, `tipe_soal`, `opsi_a`, `opsi_b`, `opsi_c`, `opsi_d`, `opsi_e`, `kunci_jawaban`, `status`, `angkatan`) VALUES
(175, 3, 3, NULL, 'Apa yang dimaksud dengan perangkat keras komputer?', 'pg', 'Program komputer', 'Bagian fisik komputer', 'Jaringan internet', 'Sistem operasi', 'Data digital', 'B', 'aktif', 'X'),
(176, 3, 3, NULL, 'Contoh perangkat lunak adalah...', 'pg', 'Keyboard', 'Monitor', 'Mouse', 'Microsoft Word', 'Printer', 'D', 'aktif', 'X'),
(177, 3, 3, NULL, 'CPU merupakan singkatan dari...', 'pg', 'Central Program Unit', 'Central Processing Unit', 'Computer Processing Unit', 'Control Processing Unit', 'Central Printer Unit', 'B', 'aktif', 'X'),
(178, 3, 3, NULL, 'Fungsi utama RAM adalah...', 'pg', 'Menyimpan data sementara', 'Mencetak dokumen', 'Menghubungkan internet', 'Memutar video', 'Menghapus file', 'A', 'aktif', 'X'),
(179, 3, 3, NULL, 'Perangkat yang digunakan untuk mencetak dokumen disebut...', 'pg', 'Scanner', 'Speaker', 'Printer', 'Joystick', 'Webcam', 'C', 'aktif', 'X'),
(180, 3, 3, NULL, 'Sistem operasi yang bersifat open source adalah...', 'pg', 'Windows', 'macOS', 'Linux', 'IOS', 'Microsoft Office', 'C', 'aktif', 'X'),
(181, 3, 3, NULL, 'Kepanjangan dari URL adalah...', 'pg', 'Uniform Resource Locator', 'Universal Resource Link', 'Uniform Reference Link', 'Universal Record Locator', 'United Resource Locator', 'A', 'aktif', 'X'),
(182, 3, 3, NULL, 'Perangkat untuk memasukkan suara ke komputer adalah...', 'pg', 'Speaker', 'Mikrofon', 'Monitor', 'Flashdisk', 'Proyektor', 'B', 'aktif', 'X'),
(183, 3, 3, NULL, 'Yang termasuk perangkat input adalah...', 'pg', 'Printer', 'Speaker', 'Monitor', 'Keyboard', 'Proyektor', 'D', 'aktif', 'X'),
(184, 3, 3, NULL, 'Internet adalah...', 'pg', 'Perangkat keras komputer', 'Jaringan komputer global', 'Program pengolah kata', 'Aplikasi desain', 'Bahasa pemrograman', 'B', 'aktif', 'X'),
(185, 3, 3, NULL, 'Aplikasi yang digunakan untuk mengolah angka adalah...', 'pg', 'Microsoft Word', 'Microsoft Excel', 'Paint', 'PowerPoint', 'Notepad', 'B', 'aktif', 'X'),
(186, 3, 3, NULL, 'Ikon tempat penyimpanan file sementara yang dihapus adalah...', 'pg', 'This PC', 'Control Panel', 'Recycle Bin', 'Taskbar', 'Browser', 'C', 'aktif', 'X'),
(187, 3, 3, NULL, 'Fungsi browser adalah...', 'pg', 'Mengedit video', 'Menggambar', 'Menjelajah internet', 'Mencetak dokumen', 'Menyimpan data', 'C', 'aktif', 'X'),
(188, 3, 3, NULL, 'Contoh browser adalah...', 'pg', 'Photoshop', 'Chrome', 'Excel', 'CorelDraw', 'VLC', 'B', 'aktif', 'X'),
(189, 3, 3, NULL, 'Shortcut untuk menyalin teks adalah...', 'pg', 'Ctrl + V', 'Ctrl + X', 'Ctrl + C', 'Ctrl + Z', 'Ctrl + P', 'C', 'aktif', 'X'),
(190, 3, 3, NULL, 'Shortcut untuk menempel hasil salinan adalah...', 'pg', 'Ctrl + V', 'Ctrl + A', 'Ctrl + X', 'Ctrl + S', 'Ctrl + O', 'A', 'aktif', 'X'),
(191, 3, 3, NULL, 'Perangkat penyimpanan data eksternal adalah...', 'pg', 'RAM', 'ROM', 'Flashdisk', 'Processor', 'Motherboard', 'C', 'aktif', 'X'),
(192, 3, 3, NULL, 'Bahasa pemrograman digunakan untuk...', 'pg', 'Mencetak dokumen', 'Memberi perintah kepada komputer', 'Memperbaiki hardware', 'Menghapus virus', 'Menggambar manual', 'B', 'aktif', 'X'),
(193, 3, 3, NULL, 'Yang termasuk contoh bahasa pemrograman adalah...', 'pg', 'Windows', 'Linux', 'Python', 'Google', 'Excel', 'C', 'aktif', 'X'),
(194, 3, 3, NULL, 'Data yang diolah menjadi informasi disebut...', 'pg', 'Input', 'Output', 'Proses', 'Informasi', 'Komunikasi', 'A', 'aktif', 'X'),
(195, 3, 3, NULL, 'LAN merupakan singkatan dari...', 'pg', 'Local Area Network', 'Large Area Network', 'Limited Access Network', 'Long Area Network', 'Local Access Network', 'A', 'aktif', 'X'),
(196, 3, 3, NULL, 'Perangkat yang berfungsi menghubungkan komputer ke jaringan adalah...', 'pg', 'RAM', 'VGA', 'NIC', 'CPU', 'Scanner', 'C', 'aktif', 'X'),
(197, 3, 3, NULL, 'Virus komputer dapat menyebabkan...', 'pg', 'Komputer lebih cepat', 'Kerusakan data', 'Internet lebih stabil', 'Printer lebih baik', 'Layar lebih terang', 'B', 'aktif', 'X'),
(198, 3, 3, NULL, 'Program antivirus digunakan untuk...', 'pg', 'Menghapus file', 'Mencetak gambar', 'Melindungi komputer dari virus', 'Mengedit video', 'Menggambar desain', 'C', 'aktif', 'X'),
(199, 3, 3, NULL, 'Perangkat lunak presentasi adalah...', 'pg', 'Microsoft Excel', 'Microsoft PowerPoint', 'Microsoft Access', 'Paint', 'Notepad', 'B', 'aktif', 'X'),
(200, 3, 3, NULL, 'Fungsi utama modem adalah...', 'pg', 'Mencetak dokumen', 'Menghubungkan komputer ke internet', 'Menyimpan data', 'Mengolah angka', 'Menampilkan gambar', 'B', 'aktif', 'X'),
(201, 3, 3, NULL, 'File dengan ekstensi .docx biasanya dibuka menggunakan...', 'pg', 'Microsoft Word', 'Paint', 'VLC', 'Chrome', 'Photoshop', 'A', 'aktif', 'X'),
(202, 3, 3, NULL, 'Etika dalam penggunaan internet disebut...', 'pg', 'Netiket', 'Netbook', 'Notebook', 'Networking', 'Netware', 'A', 'aktif', 'X'),
(203, 3, 3, NULL, 'Cloud storage adalah...', 'pg', 'Penyimpanan data secara online', 'Perangkat pendingin komputer', 'Aplikasi antivirus', 'Program pengolah kata', 'Kabel jaringan', 'A', 'aktif', 'X'),
(204, 3, 3, NULL, 'Tujuan utama backup data adalah...', 'pg', 'Mempercepat komputer', 'Mengurangi ukuran file', 'Melindungi data dari kehilangan', 'Menghapus virus', 'Menghemat listrik', 'C', 'aktif', 'X'),
(321, 3, 17, NULL, 'Tujuan utama teks laporan hasil observasi adalah...', 'pg', 'Menghibur pembaca', 'Menyampaikan hasil pengamatan secara objektif', 'Menceritakan pengalaman pribadi', 'Mempengaruhi pembaca', 'Mengajak pembaca berdiskusi', 'B', 'aktif', 'X'),
(322, 3, 17, NULL, 'Struktur teks eksposisi yang benar adalah...', 'pg', 'Orientasi, komplikasi, resolusi', 'Tesis, argumentasi, penegasan ulang', 'Pernyataan umum, deskripsi bagian, simpulan', 'Abstraksi, orientasi, krisis', 'Pembukaan, isi, penutup', 'B', 'aktif', 'X'),
(323, 3, 17, NULL, 'Kalimat yang menggunakan bahasa baku adalah...', 'pg', 'Saya nggak tahu soal itu', 'Dia udah pergi tadi', 'Kami sedang mengerjakan tugas sekolah', 'Aku mau makan dulu', 'Mereka bilang kalo acara dibatalkan', 'C', 'aktif', 'X'),
(324, 3, 17, NULL, 'Kata imbuhan pada kata \'membacakan\' adalah...', 'pg', 'me- dan -kan', 'ber- dan -an', 'di- dan -kan', 'ter- dan -i', 'pe- dan -an', 'A', 'aktif', 'X'),
(325, 3, 17, NULL, 'Paragraf yang ide pokoknya berada di awal disebut paragraf...', 'pg', 'Induktif', 'Campuran', 'Naratif', 'Deduktif', 'Persuasif', 'D', 'aktif', 'X'),
(326, 3, 17, NULL, 'Kalimat efektif adalah kalimat yang...', 'pg', 'Panjang dan rumit', 'Mengandung banyak istilah', 'Singkat, jelas, dan tepat', 'Memiliki banyak makna', 'Menggunakan bahasa daerah', 'C', 'aktif', 'X'),
(327, 3, 17, NULL, 'Berikut yang termasuk teks negosiasi adalah...', 'pg', 'Cerita rakyat', 'Pidato', 'Tawar-menawar harga barang', 'Puisi', 'Artikel ilmiah', 'C', 'aktif', 'X'),
(328, 3, 17, NULL, 'Makna denotatif adalah...', 'pg', 'Makna kias', 'Makna sebenarnya', 'Makna tambahan', 'Makna tersembunyi', 'Makna ganda', 'B', 'aktif', 'X'),
(329, 3, 17, NULL, 'Kalimat berikut yang termasuk opini adalah...', 'pg', 'Air mendidih pada suhu 100°C', 'Indonesia merdeka tahun 1945', 'Belajar daring lebih efektif daripada belajar tatap muka', 'Bumi mengelilingi matahari', 'Jakarta adalah ibu kota Indonesia', 'C', 'aktif', 'X'),
(330, 3, 17, NULL, 'Tujuan teks prosedur adalah...', 'pg', 'Menggambarkan suatu objek', 'Menceritakan suatu kejadian', 'Memberikan langkah-langkah melakukan sesuatu', 'Menghibur pembaca', 'Menjelaskan hasil penelitian', 'C', 'aktif', 'X'),
(331, 3, 17, NULL, 'Berikut yang termasuk konjungsi kausalitas adalah...', 'pg', 'Dan', 'Tetapi', 'Karena', 'Atau', 'Lalu', 'C', 'aktif', 'X'),
(332, 3, 17, NULL, 'Pantun memiliki jumlah baris dalam satu bait sebanyak...', 'pg', '2 baris', '3 baris', '4 baris', '5 baris', '6 baris', 'C', 'aktif', 'X'),
(333, 3, 17, NULL, 'Kalimat persuasif bertujuan untuk...', 'pg', 'Menghibur', 'Menyindir', 'Mengajak atau memengaruhi', 'Menceritakan pengalaman', 'Menjelaskan proses', 'C', 'aktif', 'X'),
(334, 3, 17, NULL, 'Bagian penutup dalam surat lamaran pekerjaan biasanya berisi...', 'pg', 'Daftar riwayat hidup', 'Salam penutup dan harapan', 'Tempat tanggal lahir', 'Alamat perusahaan', 'Nama sekolah', 'B', 'aktif', 'X'),
(335, 3, 17, NULL, 'Puisi yang terikat oleh rima dan irama disebut puisi...', 'pg', 'Modern', 'Kontemporer', 'Bebas', 'Lama', 'Naratif', 'D', 'aktif', 'X'),
(336, 3, 17, NULL, 'Kata serapan adalah kata yang...', 'pg', 'Berasal dari bahasa daerah', 'Memiliki makna ganda', 'Diambil dari bahasa asing', 'Tidak memiliki imbuhan', 'Hanya digunakan dalam sastra', 'C', 'aktif', 'X'),
(337, 3, 17, NULL, 'Teks biografi berisi tentang...', 'pg', 'Langkah-langkah kerja', 'Riwayat hidup seseorang', 'Cerita khayalan', 'Pendapat penulis', 'Hasil penelitian', 'B', 'aktif', 'X'),
(338, 3, 17, NULL, 'Kalimat berikut yang termasuk kalimat aktif adalah...', 'pg', 'Buku itu dibaca Andi', 'Surat telah dikirim', 'Andi menulis surat', 'Makanan dimasak ibu', 'Pintu ditutup adik', 'C', 'aktif', 'X'),
(339, 3, 17, NULL, 'Ciri utama teks anekdot adalah...', 'pg', 'Bersifat lucu dan menyindir', 'Berisi langkah-langkah', 'Menceritakan sejarah', 'Mengandung data ilmiah', 'Berisi pujian', 'A', 'aktif', 'X'),
(340, 3, 17, NULL, 'Sinonim kata \'cerdas\' adalah...', 'pg', 'Bodoh', 'Pintar', 'Malas', 'Cepat', 'Lemah', 'B', 'aktif', 'X'),
(341, 3, 17, NULL, 'Antonim kata \'besar\' adalah...', 'pg', 'Luas', 'Panjang', 'Kecil', 'Tinggi', 'Berat', 'C', 'aktif', 'X'),
(342, 3, 17, NULL, 'Kalimat yang menggunakan tanda baca dengan benar adalah...', 'pg', 'Ibu berkata “belajarlah dengan rajin”.', 'Ibu berkata, “Belajarlah dengan rajin.”', 'Ibu berkata “Belajarlah dengan rajin.”', 'Ibu berkata: “Belajarlah dengan rajin”.', 'Ibu berkata, “belajarlah dengan rajin”.', 'B', 'aktif', 'X'),
(343, 3, 17, NULL, 'Teks editorial biasanya terdapat pada...', 'pg', 'Novel', 'Kamus', 'Surat kabar', 'Buku harian', 'Cerpen', 'C', 'aktif', 'X'),
(344, 3, 17, NULL, 'Unsur intrinsik yang berkaitan dengan tempat terjadinya cerita disebut...', 'pg', 'Tema', 'Tokoh', 'Alur', 'Latar', 'Amanat', 'D', 'aktif', 'X'),
(345, 3, 17, NULL, 'Kalimat berikut yang termasuk fakta adalah...', 'pg', 'Makanan itu sangat lezat', 'Film itu membosankan', 'Indonesia terdiri atas ribuan pulau', 'Cuaca hari ini sangat indah', 'Belajar matematika itu sulit', 'C', 'aktif', 'X'),
(346, 3, 17, NULL, 'Bagian surat resmi yang berisi identitas pengirim disebut...', 'pg', 'Isi surat', 'Lampiran', 'Kop surat', 'Penutup', 'Salam pembuka', 'C', 'aktif', 'X'),
(347, 3, 17, NULL, 'Drama adalah karya sastra yang berbentuk...', 'pg', 'Cerita pendek', 'Dialog untuk dipentaskan', 'Puisi', 'Artikel', 'Pidato', 'B', 'aktif', 'X'),
(348, 3, 17, NULL, 'Kalimat majemuk adalah kalimat yang...', 'pg', 'Memiliki satu subjek', 'Memiliki satu predikat', 'Memiliki lebih dari satu klausa', 'Tidak memiliki objek', 'Sangat pendek', 'C', 'aktif', 'X'),
(349, 3, 17, NULL, 'Tujuan utama membuat ringkasan adalah...', 'pg', 'Menambah isi bacaan', 'Mengubah cerita', 'Memendekkan bacaan tanpa menghilangkan inti', 'Menghibur pembaca', 'Mengganti isi teks', 'C', 'aktif', 'X'),
(350, 3, 17, NULL, 'Kata tanya yang digunakan untuk menanyakan alasan adalah...', 'pg', 'Apa', 'Siapa', 'Kapan', 'Mengapa', 'Di mana', 'D', 'aktif', 'X'),
(381, 3, 19, NULL, 'What is the meaning of good morning', 'pg', 'Good night', 'Good afternoon', 'Good morning', 'Goodbye', 'Thank you', 'C', 'aktif', 'X'),
(382, 3, 19, NULL, 'How do you say terima kasih in English', 'pg', 'Sorry', 'Please', 'Thanks', 'Hello', 'Welcome', 'C', 'aktif', 'X'),
(383, 3, 19, NULL, 'What is the opposite of big', 'pg', 'Tall', 'Large', 'Small', 'Wide', 'Heavy', 'C', 'aktif', 'X'),
(384, 3, 19, NULL, 'Choose the correct greeting for the evening', 'pg', 'Good morning', 'Good evening', 'Goodbye', 'See you', 'Good night', 'B', 'aktif', 'X'),
(385, 3, 19, NULL, 'What is the English word for buku', 'pg', 'Pen', 'Book', 'Bag', 'Chair', 'Table', 'B', 'aktif', 'X'),
(386, 3, 19, NULL, 'I ___ a student', 'pg', 'is', 'am', 'are', 'be', 'were', 'B', 'aktif', 'X'),
(387, 3, 19, NULL, 'They ___ playing football', 'pg', 'is', 'am', 'are', 'was', 'be', 'C', 'aktif', 'X'),
(388, 3, 19, NULL, 'What is the synonym of happy', 'pg', 'Sad', 'Angry', 'Glad', 'Tired', 'Hungry', 'C', 'aktif', 'X'),
(389, 3, 19, NULL, 'Choose the correct sentence', 'pg', 'She go to school', 'She goes to school', 'She going to school', 'She gone to school', 'She is go school', 'B', 'aktif', 'X'),
(390, 3, 19, NULL, 'What is the meaning of library', 'pg', 'Market', 'School', 'Hospital', 'Library', 'Office', 'D', 'aktif', 'X'),
(391, 3, 19, NULL, 'The cat is ___ the table', 'pg', 'in', 'on', 'at', 'under', 'between', 'B', 'aktif', 'X'),
(392, 3, 19, NULL, 'What is the past form of go', 'pg', 'Goed', 'Gone', 'Went', 'Going', 'Goes', 'C', 'aktif', 'X'),
(393, 3, 19, NULL, 'Choose the correct pronoun for Rina', 'pg', 'He', 'They', 'It', 'She', 'We', 'D', 'aktif', 'X'),
(394, 3, 19, NULL, 'What is the antonym of clean', 'pg', 'Dirty', 'Fresh', 'Neat', 'Bright', 'Soft', 'A', 'aktif', 'X'),
(395, 3, 19, NULL, 'My father ___ a teacher', 'pg', 'are', 'am', 'is', 'be', 'were', 'C', 'aktif', 'X'),
(396, 3, 19, NULL, 'What does doctor mean', 'pg', 'Guru', 'Dokter', 'Petani', 'Polisi', 'Sopir', 'B', 'aktif', 'X'),
(397, 3, 19, NULL, 'Choose the correct question', 'pg', 'Where you live', 'Where do you live', 'Where does you live', 'Where are live', 'Where living you', 'B', 'aktif', 'X'),
(398, 3, 19, NULL, 'What is the plural form of child', 'pg', 'Childs', 'Children', 'Childes', 'Childrens', 'Child', 'B', 'aktif', 'X'),
(399, 3, 19, NULL, 'I like ___ music', 'pg', 'listen', 'listens', 'listening', 'listened', 'to listen', 'C', 'aktif', 'X'),
(400, 3, 19, NULL, 'What is the meaning of beautiful', 'pg', 'Ugly', 'Beautiful', 'Small', 'Fast', 'Cheap', 'B', 'aktif', 'X'),
(401, 3, 19, NULL, 'Choose the correct sentence', 'pg', 'They is students', 'They are students', 'They am students', 'They be students', 'They was students', 'B', 'aktif', 'X'),
(402, 3, 19, NULL, 'What is the English word for meja', 'pg', 'Chair', 'Wall', 'Floor', 'Table', 'Door', 'D', 'aktif', 'X'),
(403, 3, 19, NULL, 'We ___ study every day', 'pg', 'do', 'does', 'did', 'doing', 'done', 'A', 'aktif', 'X'),
(404, 3, 19, NULL, 'What is the opposite of fast', 'pg', 'Slow', 'Quick', 'Strong', 'Smart', 'Sharp', 'A', 'aktif', 'X'),
(405, 3, 19, NULL, 'Choose the correct article', 'pg', 'a apple', 'an banana', 'an apple', 'a orange', 'an cat', 'C', 'aktif', 'X'),
(406, 3, 19, NULL, 'What is the meaning of teacher', 'pg', 'Doctor', 'Teacher', 'Student', 'Farmer', 'Police', 'B', 'aktif', 'X'),
(407, 3, 19, NULL, 'She ___ cooking in the kitchen', 'pg', 'is', 'are', 'am', 'be', 'were', 'A', 'aktif', 'X'),
(408, 3, 19, NULL, 'What is the past form of eat', 'pg', 'Ate', 'Eated', 'Eating', 'Eats', 'Eat', 'A', 'aktif', 'X'),
(409, 3, 19, NULL, 'Choose the correct sentence', 'pg', 'He do homework', 'He does homework', 'He doing homework', 'He done homework', 'He dids homework', 'B', 'aktif', 'X'),
(410, 3, 19, NULL, 'What is the meaning of hospital', 'pg', 'School', 'Market', 'Hospital', 'Office', 'Library', 'C', 'aktif', 'X');

-- --------------------------------------------------------

--
-- Table structure for table `guru`
--

CREATE TABLE `guru` (
  `id_user` bigint(20) UNSIGNED NOT NULL,
  `nuptk` varchar(50) NOT NULL,
  `nama_guru` varchar(100) NOT NULL,
  `id_mapel` int(11) NOT NULL,
  `email` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `guru`
--

INSERT INTO `guru` (`id_user`, `nuptk`, `nama_guru`, `id_mapel`, `email`) VALUES
(34, '5059775676130303', 'Mitra Pranata, S.Kom', 4, 'pranatamitra@gmail.com'),
(35, '9860779680130012', 'Aditya Pranata, S.Kom', 3, 'adityapranataa1@gmail.com'),
(36, '1344766668120003', 'Donny Permana Bangun, S.Pd', 2, 'donnybangun1210@gmail.com');

-- --------------------------------------------------------

--
-- Table structure for table `hasil_ujian`
--

CREATE TABLE `hasil_ujian` (
  `id_hasil` int(11) NOT NULL,
  `id_ujian` int(11) NOT NULL,
  `id_siswa` int(11) NOT NULL,
  `nilai` float DEFAULT 0,
  `benar` int(11) DEFAULT 0,
  `salah` int(11) DEFAULT 0,
  `ragu_ragu` int(11) DEFAULT 0,
  `jawaban_siswa` text DEFAULT NULL,
  `tanggal_selesai` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jawaban_siswa`
--

CREATE TABLE `jawaban_siswa` (
  `id_jawaban` int(11) NOT NULL,
  `id_ujian` int(11) NOT NULL,
  `id_soal` int(11) NOT NULL,
  `jawaban` varchar(5) DEFAULT NULL,
  `is_benar` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jawaban_siswa`
--

INSERT INTO `jawaban_siswa` (`id_jawaban`, `id_ujian`, `id_soal`, `jawaban`, `is_benar`) VALUES
(1, 1, 3, 'A', 0),
(2, 1, 4, 'B', 0),
(3, 1, 5, 'A', 0),
(4, 1, 6, 'B', 1),
(5, 1, 7, 'B', 0),
(6, 2, 3, 'B', 1),
(7, 2, 4, 'A', 1),
(8, 2, 5, 'B', 1),
(9, 2, 6, 'B', 1),
(10, 2, 7, 'D', 1),
(11, 3, 3, 'B', 1),
(12, 3, 4, 'A', 1),
(13, 3, 5, 'B', 1),
(14, 3, 6, 'B', 1),
(15, 3, 7, 'D', 1),
(16, 4, 10, 'A', 1),
(17, 4, 11, 'A', 1),
(18, 4, 13, '', 0),
(19, 4, 16, 'E', 1),
(20, 4, 17, 'E', 0),
(21, 5, 3, 'B', 1),
(22, 5, 4, 'A', 1),
(23, 5, 5, 'B', 1),
(24, 5, 6, 'B', 1),
(25, 5, 7, 'D', 1),
(26, 5, 14, 'B', 1),
(27, 5, 15, 'D', 1),
(28, 6, 3, 'B', 1),
(29, 6, 4, 'A', 1),
(30, 6, 5, 'B', 1),
(31, 6, 6, 'B', 1),
(32, 6, 7, 'D', 1),
(33, 6, 14, 'B', 1),
(34, 6, 15, 'D', 1),
(35, 7, 20, 'C', 1),
(36, 7, 21, 'C', 1),
(37, 7, 22, 'C', 1),
(38, 7, 23, 'C', 1),
(39, 7, 24, 'C', 1),
(40, 7, 25, 'D', 1),
(41, 7, 26, 'A', 1),
(42, 7, 27, 'C', 1),
(43, 7, 28, 'C', 1),
(44, 7, 29, 'B', 1),
(45, 7, 30, 'C', 1),
(46, 7, 31, 'B', 0),
(47, 7, 32, 'B', 1),
(48, 7, 33, 'C', 1),
(49, 7, 34, 'B', 1),
(50, 7, 35, 'C', 1),
(51, 7, 36, 'D', 0),
(52, 7, 37, 'C', 1),
(53, 7, 38, 'C', 1),
(54, 7, 39, 'C', 1),
(55, 7, 40, 'C', 1),
(56, 7, 41, 'B', 0),
(57, 7, 42, 'B', 0),
(58, 7, 43, 'C', 1),
(59, 7, 44, 'C', 1),
(60, 7, 45, 'D', 0),
(61, 7, 46, 'B', 0),
(62, 7, 47, 'C', 0),
(63, 7, 48, 'C', 0),
(64, 7, 49, 'B', 0),
(65, 8, 50, 'B', 1),
(66, 8, 51, 'C', 1),
(67, 8, 52, 'C', 1),
(68, 8, 53, 'D', 1),
(69, 8, 54, 'B', 1),
(70, 8, 55, 'C', 1),
(71, 8, 56, 'B', 1),
(72, 8, 57, 'B', 1),
(73, 8, 58, 'C', 1),
(74, 8, 59, 'B', 1),
(75, 8, 60, 'B', 1),
(76, 8, 61, 'B', 1),
(77, 8, 62, 'A', 1),
(78, 8, 63, 'B', 1),
(79, 8, 64, 'C', 1),
(80, 8, 65, 'B', 1),
(81, 8, 66, 'B', 1),
(82, 8, 67, 'A', 1),
(83, 8, 68, 'B', 0),
(84, 8, 69, 'A', 1),
(85, 8, 70, 'B', 1),
(86, 8, 71, 'C', 1),
(87, 8, 72, 'B', 1),
(88, 8, 73, 'B', 1),
(89, 8, 74, 'B', 1),
(90, 8, 75, 'B', 1),
(91, 8, 76, 'B', 1),
(92, 8, 77, 'B', 1),
(93, 8, 78, 'B', 1),
(94, 8, 79, 'E', 0),
(95, 9, 80, 'B', 1),
(96, 9, 81, 'B', 1),
(97, 9, 82, 'B', 1),
(98, 9, 83, 'B', 0),
(99, 9, 84, 'B', 0),
(100, 9, 85, 'B', 1),
(101, 9, 86, 'B', 1),
(102, 9, 87, 'B', 0),
(103, 9, 88, 'C', 0),
(104, 9, 89, 'B', 1),
(105, 9, 90, 'B', 1),
(106, 9, 91, 'B', 1),
(107, 9, 92, 'A', 0),
(108, 9, 93, 'C', 0),
(109, 9, 94, 'B', 1),
(110, 9, 95, 'B', 1),
(111, 9, 96, 'B', 1),
(112, 9, 97, 'B', 0),
(113, 9, 98, 'C', 0),
(114, 9, 99, 'B', 1),
(115, 9, 100, 'C', 0),
(116, 9, 101, 'B', 1),
(117, 9, 102, 'C', 0),
(118, 9, 103, 'B', 1),
(119, 9, 104, 'C', 0),
(120, 9, 105, 'A', 0),
(121, 9, 106, 'B', 1),
(122, 9, 107, 'C', 0),
(123, 9, 108, 'B', 1),
(124, 9, 109, 'B', 1),
(125, 10, 20, 'A', 0),
(126, 10, 21, 'A', 0),
(127, 10, 22, 'A', 0),
(128, 10, 23, 'A', 0),
(129, 10, 24, 'A', 0),
(130, 10, 25, 'A', 0),
(131, 10, 26, 'A', 1),
(132, 10, 27, 'A', 0),
(133, 10, 28, 'A', 0),
(134, 10, 29, 'A', 0),
(135, 10, 30, 'B', 0),
(136, 10, 31, 'A', 0),
(137, 10, 32, 'A', 0),
(138, 10, 33, 'B', 0),
(139, 10, 34, 'A', 0),
(140, 10, 35, 'B', 0),
(141, 10, 36, 'B', 0),
(142, 10, 37, 'B', 0),
(143, 10, 38, 'A', 0),
(144, 10, 39, 'B', 0),
(145, 10, 40, 'B', 0),
(146, 10, 41, 'B', 0),
(147, 10, 42, 'A', 0),
(148, 10, 43, 'A', 0),
(149, 10, 44, 'B', 0),
(150, 10, 45, 'C', 0),
(151, 10, 46, 'A', 0),
(152, 10, 47, 'C', 0),
(153, 10, 48, 'B', 0),
(154, 10, 49, 'A', 0),
(155, 11, 111, 'B', 1),
(156, 11, 112, 'C', 0),
(157, 11, 113, 'C', 1),
(158, 11, 114, 'A', 1),
(159, 11, 115, 'A', 0),
(160, 11, 116, 'E', 0),
(161, 11, 117, 'C', 1),
(162, 11, 118, 'C', 0),
(163, 11, 119, 'B', 0),
(164, 11, 120, 'C', 1),
(165, 11, 121, 'B', 0),
(166, 11, 122, 'B', 0),
(167, 11, 123, 'C', 1),
(168, 11, 124, 'A', 0),
(169, 11, 125, 'B', 0),
(170, 11, 126, 'C', 1),
(171, 11, 127, 'B', 1),
(172, 11, 128, 'E', 0),
(173, 11, 129, 'E', 0),
(174, 11, 130, 'B', 1),
(175, 11, 131, 'C', 1),
(176, 11, 132, 'C', 0),
(177, 11, 133, 'B', 0),
(178, 11, 134, 'B', 0),
(179, 11, 135, 'C', 1),
(180, 11, 136, 'B', 0),
(181, 11, 137, 'B', 1),
(182, 11, 138, 'B', 0),
(183, 11, 139, 'C', 1),
(184, 11, 140, 'D', 1),
(185, 12, 111, 'E', 0),
(186, 12, 112, 'E', 0),
(187, 12, 113, 'C', 1),
(188, 12, 114, 'C', 0),
(189, 12, 115, 'B', 0),
(190, 12, 116, 'C', 1),
(191, 12, 117, 'C', 1),
(192, 12, 118, 'B', 1),
(193, 12, 119, 'C', 1),
(194, 12, 120, 'B', 0),
(195, 12, 121, 'E', 0),
(196, 12, 122, 'D', 0),
(197, 12, 123, 'D', 0),
(198, 12, 124, 'A', 0),
(199, 12, 125, 'B', 0),
(200, 12, 126, 'B', 0),
(201, 12, 127, 'A', 0),
(202, 12, 128, 'B', 0),
(203, 12, 129, 'C', 0),
(204, 12, 130, 'B', 1),
(205, 12, 131, 'A', 0),
(206, 12, 132, 'E', 0),
(207, 12, 133, 'E', 0),
(208, 12, 134, 'E', 0),
(209, 12, 135, 'B', 0),
(210, 12, 136, 'A', 0),
(211, 12, 137, 'D', 0),
(212, 12, 138, 'E', 0),
(213, 12, 139, 'A', 0),
(214, 12, 140, 'D', 1),
(215, 13, 111, 'A', 0),
(216, 13, 112, 'A', 0),
(217, 13, 113, 'B', 0),
(218, 13, 114, 'A', 1),
(219, 13, 115, 'C', 0),
(220, 13, 116, 'A', 0),
(221, 13, 117, 'A', 0),
(222, 13, 118, 'D', 0),
(223, 13, 119, 'D', 0),
(224, 13, 120, 'C', 1),
(225, 13, 121, 'B', 0),
(226, 13, 122, 'C', 1),
(227, 13, 123, 'D', 0),
(228, 13, 124, 'B', 1),
(229, 13, 125, 'C', 0),
(230, 13, 126, 'B', 0),
(231, 13, 127, 'C', 0),
(232, 13, 128, 'B', 0),
(233, 13, 129, 'C', 0),
(234, 13, 130, 'C', 0),
(235, 13, 131, 'B', 0),
(236, 13, 132, 'E', 0),
(237, 13, 133, 'C', 1),
(238, 13, 134, 'B', 0),
(239, 13, 135, 'C', 1),
(240, 13, 136, 'B', 0),
(241, 13, 137, 'B', 1),
(242, 13, 138, 'B', 0),
(243, 13, 139, 'C', 1),
(244, 13, 140, 'B', 0),
(245, 14, 111, 'B', 1),
(246, 14, 112, 'B', 1),
(247, 14, 113, 'C', 1),
(248, 14, 114, 'B', 0),
(249, 14, 115, 'D', 1),
(250, 14, 116, 'B', 0),
(251, 14, 117, 'D', 0),
(252, 14, 118, 'B', 1),
(253, 14, 119, 'C', 1),
(254, 14, 120, 'B', 0),
(255, 14, 121, 'D', 0),
(256, 14, 122, 'B', 0),
(257, 14, 123, 'D', 0),
(258, 14, 124, 'B', 1),
(259, 14, 125, 'C', 0),
(260, 14, 126, 'B', 0),
(261, 14, 127, 'D', 0),
(262, 14, 128, 'B', 0),
(263, 14, 129, 'D', 0),
(264, 14, 130, 'C', 0),
(265, 14, 131, 'B', 0),
(266, 14, 132, 'D', 0),
(267, 14, 133, 'C', 1),
(268, 14, 134, 'B', 0),
(269, 14, 135, 'D', 0),
(270, 14, 136, 'B', 0),
(271, 14, 137, 'C', 0),
(272, 14, 138, 'C', 1),
(273, 14, 139, 'B', 0),
(274, 14, 140, 'B', 0),
(275, 15, 111, 'A', 0),
(276, 15, 112, 'B', 1),
(277, 15, 113, 'C', 1),
(278, 15, 114, 'C', 0),
(279, 15, 115, 'B', 0),
(280, 15, 116, 'B', 0),
(281, 15, 117, 'B', 0),
(282, 15, 118, 'B', 1),
(283, 15, 119, 'B', 0),
(284, 15, 120, 'C', 1),
(285, 15, 121, 'B', 0),
(286, 15, 122, 'B', 0),
(287, 15, 123, 'B', 0),
(288, 15, 124, 'B', 1),
(289, 15, 125, 'D', 1),
(290, 15, 126, 'C', 1),
(291, 15, 127, 'B', 1),
(292, 15, 128, 'B', 0),
(293, 15, 129, 'B', 0),
(294, 15, 130, 'B', 1),
(295, 15, 131, 'B', 0),
(296, 15, 132, 'B', 1),
(297, 15, 133, 'B', 0),
(298, 15, 134, 'A', 0),
(299, 15, 135, 'A', 0),
(300, 15, 136, 'B', 0),
(301, 15, 137, 'B', 1),
(302, 15, 138, 'B', 0),
(303, 15, 139, 'B', 0),
(304, 15, 140, 'C', 0),
(305, 16, 20, '', 0),
(306, 16, 21, '', 0),
(307, 16, 22, '', 0),
(308, 16, 23, '', 0),
(309, 16, 24, '', 0),
(310, 16, 25, '', 0),
(311, 16, 26, '', 0),
(312, 16, 27, '', 0),
(313, 16, 28, '', 0),
(314, 16, 29, '', 0),
(315, 16, 30, '', 0),
(316, 16, 31, '', 0),
(317, 16, 32, '', 0),
(318, 16, 33, '', 0),
(319, 16, 34, '', 0),
(320, 16, 35, '', 0),
(321, 16, 36, '', 0),
(322, 16, 37, '', 0),
(323, 16, 38, '', 0),
(324, 16, 39, '', 0),
(325, 16, 40, '', 0),
(326, 16, 41, '', 0),
(327, 16, 42, '', 0),
(328, 16, 43, '', 0),
(329, 16, 44, '', 0),
(330, 16, 45, '', 0),
(331, 16, 46, '', 0),
(332, 16, 47, '', 0),
(333, 16, 48, '', 0),
(334, 16, 49, '', 0),
(335, 17, 20, 'D', 0),
(336, 17, 21, 'C', 1),
(337, 17, 22, 'C', 1),
(338, 17, 23, '', 0),
(339, 17, 24, '', 0),
(340, 17, 25, '', 0),
(341, 17, 26, '', 0),
(342, 17, 27, '', 0),
(343, 17, 28, '', 0),
(344, 17, 29, '', 0),
(345, 17, 30, '', 0),
(346, 17, 31, '', 0),
(347, 17, 32, '', 0),
(348, 17, 33, '', 0),
(349, 17, 34, '', 0),
(350, 17, 35, '', 0),
(351, 17, 36, '', 0),
(352, 17, 37, '', 0),
(353, 17, 38, '', 0),
(354, 17, 39, '', 0),
(355, 17, 40, '', 0),
(356, 17, 41, '', 0),
(357, 17, 42, '', 0),
(358, 17, 43, '', 0),
(359, 17, 44, '', 0),
(360, 17, 45, '', 0),
(361, 17, 46, '', 0),
(362, 17, 47, '', 0),
(363, 17, 48, '', 0),
(364, 17, 49, '', 0),
(365, 18, 50, 'B', 1),
(366, 18, 51, 'D', 0),
(367, 18, 52, 'C', 1),
(368, 18, 53, 'C', 0),
(369, 18, 54, 'C', 0),
(370, 18, 55, '', 0),
(371, 18, 56, '', 0),
(372, 18, 57, '', 0),
(373, 18, 58, '', 0),
(374, 18, 59, '', 0),
(375, 18, 60, '', 0),
(376, 18, 61, '', 0),
(377, 18, 62, '', 0),
(378, 18, 63, '', 0),
(379, 18, 64, '', 0),
(380, 18, 65, '', 0),
(381, 18, 66, '', 0),
(382, 18, 67, '', 0),
(383, 18, 68, '', 0),
(384, 18, 69, '', 0),
(385, 18, 70, '', 0),
(386, 18, 71, '', 0),
(387, 18, 72, '', 0),
(388, 18, 73, '', 0),
(389, 18, 74, '', 0),
(390, 18, 75, '', 0),
(391, 18, 76, '', 0),
(392, 18, 77, '', 0),
(393, 18, 78, '', 0),
(394, 18, 79, '', 0),
(395, 19, 50, 'B', 1),
(396, 19, 51, 'D', 0),
(397, 19, 52, 'C', 1),
(398, 19, 53, 'B', 0),
(399, 19, 54, 'D', 0),
(400, 19, 55, 'D', 0),
(401, 19, 56, 'B', 1),
(402, 19, 57, 'D', 0),
(403, 19, 58, 'C', 1),
(404, 19, 59, 'D', 0),
(405, 19, 60, 'B', 1),
(406, 19, 61, 'B', 1),
(407, 19, 62, 'B', 0),
(408, 19, 63, 'B', 1),
(409, 19, 64, 'B', 0),
(410, 19, 65, 'C', 0),
(411, 19, 66, 'B', 1),
(412, 19, 67, 'A', 1),
(413, 19, 68, 'C', 0),
(414, 19, 69, 'A', 1),
(415, 19, 70, 'C', 0),
(416, 19, 71, 'D', 0),
(417, 19, 72, 'C', 0),
(418, 19, 73, 'B', 1),
(419, 19, 74, 'B', 1),
(420, 19, 75, 'D', 0),
(421, 19, 76, 'B', 1),
(422, 19, 77, 'D', 0),
(423, 19, 78, 'D', 0),
(424, 19, 79, 'B', 1);

-- --------------------------------------------------------

--
-- Table structure for table `jurusan`
--

CREATE TABLE `jurusan` (
  `id_jurusan` int(11) NOT NULL,
  `nama_program` varchar(150) NOT NULL,
  `kode` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jurusan`
--

INSERT INTO `jurusan` (`id_jurusan`, `nama_program`, `kode`) VALUES
(1, 'Teknik Konstruksi dan Perumahan', 'TKP'),
(2, 'Teknik Mesin', 'TM'),
(3, 'Teknik Otomotif', 'TO'),
(4, 'Teknik Elektronika', 'TE'),
(5, 'Teknik Ketenagalistrikan', 'TTL'),
(6, 'Pengembangan Perangkat Lunak dan Gim', 'PPLG'),
(7, 'Teknik Komputer Jaringan dan Telekomunikasi', 'TKJT'),
(8, 'Pemasaran', 'PM'),
(9, 'Manajemen Perkantoran dan Layanan Bisnis', 'MPLB'),
(10, 'Akuntansi dan Keuangan Lembaga', 'AKL'),
(11, 'Busana', 'BSN'),
(12, 'Kuliner', 'KUL');

-- --------------------------------------------------------

--
-- Table structure for table `kartu_rfid`
--

CREATE TABLE `kartu_rfid` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `uid_rfid` varchar(50) NOT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `tanggal_aktivasi` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kartu_rfid`
--

INSERT INTO `kartu_rfid` (`id`, `siswa_id`, `uid_rfid`, `status`, `tanggal_aktivasi`, `created_at`) VALUES
(1, 2, 'D3-4A-5B-6C', 'nonaktif', NULL, '2026-05-08 14:08:51'),
(2, 4, 'A1-B2-C3-D4', 'nonaktif', NULL, '2026-05-08 14:08:51'),
(3, 5, 'E5-F6-G7-H8', 'nonaktif', NULL, '2026-05-08 14:08:51');

-- --------------------------------------------------------

--
-- Table structure for table `kelas`
--

CREATE TABLE `kelas` (
  `id_kelas` int(11) NOT NULL,
  `nama_kelas` varchar(20) NOT NULL,
  `id_jurusan` int(11) DEFAULT NULL,
  `id_konsentrasi` int(11) DEFAULT NULL,
  `tingkat` enum('X','XI','XII') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kelas`
--

INSERT INTO `kelas` (`id_kelas`, `nama_kelas`, `id_jurusan`, `id_konsentrasi`, `tingkat`) VALUES
(1, 'X OTOMOTIF 1', 3, NULL, 'X'),
(2, 'X OTOMOTIF 2', 3, NULL, 'X'),
(3, 'X OTOMOTIF 3', 3, NULL, 'X'),
(4, 'X OTOMOTIF 4', 3, NULL, 'X'),
(5, 'X TKP', 1, NULL, 'X'),
(6, 'X TAV', 4, NULL, 'X'),
(7, 'X TITL', 5, NULL, 'X'),
(8, 'X TP', 2, NULL, 'X'),
(9, 'X TKJ', 7, NULL, 'X'),
(10, 'X RPL', 6, NULL, 'X'),
(11, 'X MP', 9, NULL, 'X'),
(12, 'X AK', 10, NULL, 'X'),
(13, 'X BRL', 8, NULL, 'X'),
(14, 'X DPB', 11, NULL, 'X'),
(15, 'X KLR 1', 12, NULL, 'X'),
(16, 'X KLR 2', 12, NULL, 'X'),
(18, 'XI TAV', 4, 6, 'XI'),
(19, 'XI TITL', 5, 7, 'XI'),
(20, 'XI TP', 2, 2, 'XI'),
(21, 'XI TKR', 3, 3, 'XI'),
(22, 'XI TBKR', 3, 5, 'XI'),
(23, 'XI TSM 1', 3, 4, 'XI'),
(24, 'XI TSM 2', 3, 4, 'XI'),
(25, 'XI TKJ', 7, 9, 'XI'),
(26, 'XI RPL', 6, 8, 'XI'),
(27, 'XI MP', 9, 11, 'XI'),
(28, 'XI AK', 10, 12, 'XI'),
(29, 'XI BRL', 8, 10, 'XI'),
(30, 'XI DPB', 11, 13, 'XI'),
(31, 'XI KLR ', 12, 14, 'XI'),
(32, 'XII TKP', 1, 1, 'XII'),
(33, 'XII TAV', 4, 6, 'XII'),
(34, 'XII TITL', 5, 7, 'XII'),
(35, 'XII TP', 2, 2, 'XII'),
(36, 'XII TKR 1', 3, 3, 'XII'),
(37, 'XII TKR 2', 3, 3, 'XII'),
(38, 'XII TBKR', 3, 5, 'XII'),
(39, 'XII TSM 1', 3, 4, 'XII'),
(40, 'XII TSM 2', 3, 4, 'XII'),
(41, 'XII TKJ', 7, 9, 'XII'),
(42, 'XII RPL', 6, 8, 'XII'),
(43, 'XII MP', 9, 11, 'XII'),
(44, 'XII AK', 10, 12, 'XII'),
(45, 'XII BRL', 8, 10, 'XII'),
(46, 'XII DPB', 11, 13, 'XII'),
(47, 'XII KLR ', 12, 14, 'XII');

-- --------------------------------------------------------

--
-- Table structure for table `konsentrasi`
--

CREATE TABLE `konsentrasi` (
  `id_konsentrasi` int(11) NOT NULL,
  `id_jurusan` int(11) NOT NULL,
  `nama_konsentrasi` varchar(150) NOT NULL,
  `kode` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `konsentrasi`
--

INSERT INTO `konsentrasi` (`id_konsentrasi`, `id_jurusan`, `nama_konsentrasi`, `kode`) VALUES
(1, 1, 'Teknik Konstruksi dan Perumahan', 'TKP'),
(2, 2, 'Teknik Pemesinan', 'TP'),
(3, 3, 'Teknik Kendaraan Ringan', 'TKR'),
(4, 3, 'Teknik Sepeda Motor', 'TSM'),
(5, 3, 'Teknik Bodi Kendaraan Ringan', 'TBKR'),
(6, 4, 'Teknik Audio Video', 'TAV'),
(7, 5, 'Teknik Instalasi Tenaga Listrik', 'TITL'),
(8, 6, 'Rekayasa Perangkat Lunak', 'RPL'),
(9, 7, 'Teknik Komputer dan Jaringan', 'TKJ'),
(10, 8, 'Bisnis Retail', 'BRL'),
(11, 9, 'Manajemen Perkantoran', 'MP'),
(12, 10, 'Akuntansi', 'AK'),
(13, 11, 'Desain dan Produksi Busana', 'DPB'),
(14, 12, 'Kuliner', 'KLR');

-- --------------------------------------------------------

--
-- Table structure for table `log_rfid`
--

CREATE TABLE `log_rfid` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `uid_rfid` varchar(50) NOT NULL,
  `waktu` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `log_rfid`
--

INSERT INTO `log_rfid` (`id`, `siswa_id`, `uid_rfid`, `waktu`) VALUES
(14, 2, 'D3-4A-5B-6C', '2026-05-09 11:10:48'),
(15, 4, 'A1-B2-C3-D4', '2026-05-09 11:20:54'),
(16, 5, 'E5-F6-G7-H8', '2026-05-09 12:46:40');

-- --------------------------------------------------------

--
-- Table structure for table `mapel`
--

CREATE TABLE `mapel` (
  `id_mapel` int(11) NOT NULL,
  `nama_mapel` varchar(100) NOT NULL,
  `jenis_mapel` enum('umum','kejuruan') NOT NULL DEFAULT 'umum',
  `id_jurusan` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mapel`
--

INSERT INTO `mapel` (`id_mapel`, `nama_mapel`, `jenis_mapel`, `id_jurusan`) VALUES
(2, 'Matematika', 'umum', NULL),
(3, 'Informatika', 'umum', NULL),
(4, 'PPLG (Pengembangan Perangkat Lunak dan Gim)', 'kejuruan', 6),
(6, 'TKP (Teknik Konstruksi dan Perumahan)', 'kejuruan', 1),
(7, 'TM (Teknik Mesin)', 'kejuruan', 2),
(8, 'TO (Teknik Otomotif)', 'kejuruan', 3),
(9, 'TE (Teknik Elektronika)', 'kejuruan', 4),
(10, 'TTL (Teknik Ketenagalistrikan)', 'kejuruan', 5),
(11, 'TKJT (Teknik Komputer Jaringan & Telekomunikasi)', 'kejuruan', 7),
(12, 'PM (Pemasaran)', 'kejuruan', 8),
(13, 'MPLB (Manajemen Perkantoran & Layanan Bisnis)', 'kejuruan', 9),
(14, 'AKL (Akuntansi dan Keuangan Lembaga)', 'kejuruan', 10),
(15, 'BSN (Busana)', 'kejuruan', 11),
(16, 'KUL (Kuliner)', 'kejuruan', 12),
(17, 'Bahasa Indonesia', 'umum', NULL),
(19, 'Bahasa Inggris', 'umum', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `nilai`
--

CREATE TABLE `nilai` (
  `id_nilai` int(11) NOT NULL,
  `id_siswa` int(11) DEFAULT NULL,
  `id_sesi` int(11) DEFAULT NULL,
  `skor` decimal(5,2) DEFAULT NULL,
  `waktu_selesai` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ruang_ujian`
--

CREATE TABLE `ruang_ujian` (
  `id_ruang` int(11) NOT NULL,
  `nama_ruang` varchar(50) NOT NULL,
  `lokasi` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ruang_ujian`
--

INSERT INTO `ruang_ujian` (`id_ruang`, `nama_ruang`, `lokasi`) VALUES
(1, 'RUANG 1', NULL),
(3, 'RUANG 3', NULL),
(4, 'RUANG 4', NULL),
(5, 'RUANG 5', NULL),
(7, 'RUANG 6', NULL),
(8, 'RUANG 7', NULL),
(9, 'RUANG 8', NULL),
(10, 'RUANG 9', NULL),
(11, 'RUANG 10', NULL),
(12, 'RUANG 11', NULL),
(13, 'RUANG 12', NULL),
(14, 'RUANG 13', NULL),
(15, 'RUANG 14', NULL),
(16, 'RUANG 15', NULL),
(17, 'RUANG 16', NULL),
(18, 'RUANG 17', NULL),
(19, 'RUANG 18', NULL),
(20, 'RUANG 19', NULL),
(21, 'RUANG 20', NULL),
(22, 'RUANG 21', NULL),
(23, 'RUANG 22', NULL),
(24, 'RUANG 23', NULL),
(25, 'RUANG 24', NULL),
(26, 'RUANG 25', NULL),
(27, 'RUANG 2', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sesi_ujian`
--

CREATE TABLE `sesi_ujian` (
  `id_sesi` int(11) NOT NULL,
  `nama_ujian` varchar(100) DEFAULT NULL,
  `jenis_ujian` enum('MID','SEMESTER') DEFAULT NULL,
  `id_kelas` int(11) DEFAULT NULL,
  `id_mapel` int(11) DEFAULT NULL,
  `tgl_mulai` datetime DEFAULT NULL,
  `durasi` int(11) DEFAULT NULL,
  `token` varchar(10) DEFAULT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'nonaktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sesi_ujian`
--

INSERT INTO `sesi_ujian` (`id_sesi`, `nama_ujian`, `jenis_ujian`, `id_kelas`, `id_mapel`, `tgl_mulai`, `durasi`, `token`, `status`) VALUES
(6, 'Uji Coba 4 April 2026 Matematika', '', 12, 2, '2026-04-04 23:30:00', 10, '06IQB', 'nonaktif'),
(7, 'Uji Coba 4 April 2026 Informatika', '', 12, 3, '2026-04-04 23:45:00', 10, 'TGJFJ', 'nonaktif'),
(10, 'Uji Coba 7 Mei 2026 Bahasa Indonesia', '', NULL, 17, '2026-05-07 21:40:00', 60, '9868A', 'nonaktif'),
(11, 'Uji Coba 9 Mei 2026', '', NULL, 17, '2026-05-09 18:10:00', 60, '26F7C', 'nonaktif'),
(12, 'Uji Coba 10 Mei 2026', '', NULL, 2, '2026-05-09 19:30:00', 10, 'FFA1C', 'nonaktif');

-- --------------------------------------------------------

--
-- Table structure for table `sesi_ujian_kelas`
--

CREATE TABLE `sesi_ujian_kelas` (
  `id` int(11) NOT NULL,
  `id_sesi` int(11) NOT NULL,
  `id_kelas` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sesi_ujian_kelas`
--

INSERT INTO `sesi_ujian_kelas` (`id`, `id_sesi`, `id_kelas`) VALUES
(59, 6, 1),
(60, 6, 2),
(61, 6, 3),
(62, 6, 4),
(67, 6, 5),
(64, 6, 6),
(65, 6, 7),
(68, 6, 8),
(66, 6, 9),
(63, 6, 10),
(58, 6, 11),
(53, 6, 12),
(54, 6, 13),
(55, 6, 14),
(56, 6, 15),
(57, 6, 16),
(43, 7, 1),
(44, 7, 2),
(45, 7, 3),
(46, 7, 4),
(51, 7, 5),
(48, 7, 6),
(49, 7, 7),
(52, 7, 8),
(50, 7, 9),
(47, 7, 10),
(42, 7, 11),
(37, 7, 12),
(38, 7, 13),
(39, 7, 14),
(40, 7, 15),
(41, 7, 16),
(114, 10, 1),
(115, 10, 2),
(116, 10, 3),
(117, 10, 4),
(112, 10, 5),
(110, 10, 6),
(118, 10, 7),
(113, 10, 8),
(111, 10, 9),
(109, 10, 10),
(107, 10, 11),
(103, 10, 12),
(108, 10, 13),
(104, 10, 14),
(105, 10, 15),
(106, 10, 16),
(131, 11, 1),
(132, 11, 2),
(133, 11, 3),
(134, 11, 4),
(129, 11, 5),
(127, 11, 6),
(135, 11, 7),
(130, 11, 8),
(128, 11, 9),
(126, 11, 10),
(124, 11, 11),
(120, 11, 12),
(125, 11, 13),
(121, 11, 14),
(122, 11, 15),
(123, 11, 16),
(147, 12, 1),
(148, 12, 2),
(149, 12, 3),
(150, 12, 4),
(145, 12, 5),
(143, 12, 6),
(151, 12, 7),
(146, 12, 8),
(144, 12, 9),
(142, 12, 10),
(140, 12, 11),
(136, 12, 12),
(141, 12, 13),
(137, 12, 14),
(138, 12, 15),
(139, 12, 16);

-- --------------------------------------------------------

--
-- Table structure for table `siswa`
--

CREATE TABLE `siswa` (
  `id_siswa` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `id_kelas` int(11) DEFAULT NULL,
  `id_ruang` int(11) DEFAULT NULL,
  `nisn` varchar(20) DEFAULT NULL,
  `nama_siswa` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `siswa`
--

INSERT INTO `siswa` (`id_siswa`, `id_user`, `id_kelas`, `id_ruang`, `nisn`, `nama_siswa`) VALUES
(2, 5, 10, NULL, '0042450221', 'M. MAULANA AULIA'),
(4, 7, 12, NULL, '0072163465', 'ASMAYANI'),
(5, 8, 11, NULL, '0077564865', 'BEBI FEBI AULYA'),
(6, 9, 44, NULL, '0082483943', 'DIMAS PRAYOGI'),
(9, 12, 44, NULL, '0086682998', 'IRA RAHMAWATI'),
(10, 13, 44, NULL, '3077093719', 'KEISYA ZAKIRASIKHA AHMAD'),
(11, 14, 44, NULL, '0082102626', 'KESYA ANINDYA'),
(12, 15, 44, NULL, '0037264032', 'KESYA MUTIA HASANA'),
(13, 16, 44, NULL, '0089665263', 'KIRANA AZZAHRA'),
(14, 17, 44, NULL, '0086173300', 'NAINA ROSABEL LIE'),
(15, 18, 44, NULL, '0073023997', 'NUR ALIZA NAZIHA'),
(16, 19, 44, NULL, '0089148948', 'SITI CHEISYA AULIA BR. PURBA');

-- --------------------------------------------------------

--
-- Table structure for table `ujian`
--

CREATE TABLE `ujian` (
  `id_ujian` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_sesi` int(11) DEFAULT NULL,
  `id_mapel` int(11) DEFAULT NULL,
  `judul_ujian` varchar(255) NOT NULL,
  `kode_ujian` varchar(50) DEFAULT NULL,
  `waktu_mulai` datetime DEFAULT NULL,
  `waktu_selesai` datetime DEFAULT NULL,
  `durasi` int(11) NOT NULL COMMENT 'Durasi Menit',
  `acak_soal` tinyint(1) DEFAULT 0,
  `status` enum('sedang_dikerjakan','selesai','gagal') DEFAULT 'sedang_dikerjakan',
  `nilai` decimal(5,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ujian`
--

INSERT INTO `ujian` (`id_ujian`, `id_user`, `id_sesi`, `id_mapel`, `judul_ujian`, `kode_ujian`, `waktu_mulai`, `waktu_selesai`, `durasi`, `acak_soal`, `status`, `nilai`) VALUES
(1, 7, 2, 2, 'Ujian Semester Ganjil Tahun 2026', '0PC6U', '2026-04-03 01:24:15', '2026-04-03 01:34:15', 10, 0, 'selesai', 20.00),
(2, 7, 2, 2, 'Ujian Semester Ganjil Tahun 2026', 'SUQRO', '2026-04-03 01:36:00', '2026-04-03 01:46:00', 10, 0, 'selesai', 100.00),
(3, 7, 2, 2, 'Ujian Semester Ganjil Tahun 2026', 'SUQRO', '2026-04-03 01:42:36', '2026-04-03 01:52:36', 10, 0, 'selesai', 100.00),
(4, 5, 3, 4, 'Ujian Semester GenapTahun 2026', 'NN7J2', '2026-04-03 21:00:14', '2026-04-03 21:02:42', 2, 0, 'selesai', 60.00),
(6, 9, 5, 2, 'Contoh', 'XOXVT', '2026-04-04 14:56:48', '2026-04-04 14:58:13', 5, 0, 'selesai', 100.00),
(7, 5, 6, 2, 'Uji Coba 4 April 2026', '06IQB', '2026-04-04 23:31:02', '2026-04-04 23:34:48', 10, 0, 'selesai', 70.00),
(8, 5, 7, 3, 'Uji Coba 4 April 2026 Informatika', 'TGJFJ', '2026-04-04 23:45:16', '2026-04-04 23:48:53', 10, 0, 'selesai', 93.30),
(9, 5, 8, 4, 'Uji Coba 4 April 2026 Rekayasa Perangkat Lunak (DK)', 'H1Z7W', '2026-04-05 00:01:14', '2026-04-05 00:11:37', 10, 0, 'selesai', 56.70),
(10, 5, 9, 2, 'Ujian Semester Genap Pendidikan Agama X Tahun 2026', '9B242', '2026-04-09 16:27:01', '2026-04-09 16:28:16', 10, 0, 'selesai', 3.30),
(11, 5, 10, 17, 'Uji Coba 7 Mei 2026', '9868A', '2026-05-07 21:40:43', '2026-05-07 21:47:45', 60, 0, 'selesai', 46.70),
(12, 7, 10, 17, 'Uji Coba 7 Mei 2026', '9868A', '2026-05-07 21:50:07', '2026-05-07 21:51:23', 60, 0, 'selesai', 23.30),
(13, 8, 10, 17, 'Uji Coba 7 Mei 2026', '9868A', '2026-05-07 22:07:32', '2026-05-07 22:09:12', 60, 0, 'selesai', 26.70),
(14, 5, 11, 17, 'Uji Coba 9 Mei 2026', '26F7C', '2026-05-09 18:12:33', '2026-05-09 18:18:37', 60, 0, 'selesai', 30.00),
(15, 7, 11, 17, 'Uji Coba 9 Mei 2026', '26F7C', '2026-05-09 18:21:07', '2026-05-09 19:18:16', 60, 0, 'selesai', 36.70),
(16, 5, 12, 2, 'Uji Coba 10 Mei 2026', 'FFA1C', '2026-05-09 19:30:31', '2026-05-09 19:31:01', 10, 0, 'selesai', 0.00),
(17, 7, 12, 2, 'Uji Coba 10 Mei 2026', 'FFA1C', '2026-05-09 19:32:08', '2026-05-09 19:36:00', 10, 0, 'selesai', 6.70),
(18, 8, 13, 3, 'Uji Coba 11 Mei 2026', 'A3C6A', '2026-05-09 19:50:50', '2026-05-09 19:53:34', 120, 0, 'selesai', 6.70),
(19, 5, 13, 3, 'Uji Coba 11 Mei 2026', 'A3C6A', '2026-05-09 19:58:57', '2026-05-09 20:02:28', 120, 0, 'selesai', 46.70);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','guru','siswa') NOT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'nonaktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id_user`, `username`, `password`, `role`, `status`) VALUES
(3, 'admin', '$2y$10$BCXXTRB00ox8pMeAfbuXGexSseEDPrSKG2lQSFtbDwGERQ1k/b01q', 'admin', 'nonaktif'),
(5, '0042450221', '$2y$10$bksu/VEkENNwW5yACIw2a.KmiVUKFAjyFl4cvLEaE4mS5OwXLHOdC', 'siswa', 'nonaktif'),
(6, '0084259370', '$2y$10$ZQTNAZKLg/FPPhwhqhZAcerVcykHIJInOD/d1G6JrPqdN5xhwQ6uu', 'siswa', 'nonaktif'),
(7, '0072163465', '$2y$10$eYCXY4vfQM32UyzNXxPJA.zHMdeCjBLC85kzdDgI.r9/F1/K9N.Zy', 'siswa', 'nonaktif'),
(8, '0077564865', '$2y$10$lXzzUewfFvQBtUkabl8M1OBy4CRp8j2UJSnQrWOVDt1JjPifLTP8C', 'siswa', 'nonaktif'),
(9, '0082483943', '$2y$10$D9vXo1c/pCybEXWyPby.jumhIbFsovCtNu.25vU1MNHSLhODkaMpi', 'siswa', 'nonaktif'),
(12, '0086682998', '$2y$10$1B4i39sX06pgctR6l/v6HedmK2TuMHZRTUXFqU.z1nZiHgyJJ2RI.', 'siswa', 'nonaktif'),
(13, '3077093719', '$2y$10$BY/ex78IJ8xINvadUDnp8esAu4lvUIjtmO3eT3nlPglOIUUHUxKyS', 'siswa', 'nonaktif'),
(14, '0082102626', '$2y$10$KHtO5/P4U68d0/lTaRq6LeWV46t2RS4kU6PicnbnXTYzl4lzzJuqe', 'siswa', 'nonaktif'),
(15, '0037264032', '$2y$10$Z..S9g8gCEXtNoejVJ/w0O7HEqyljV3l73HwghUvY6CiJ1SZGDi0u', 'siswa', 'nonaktif'),
(16, '0089665263', '$2y$10$aMTE6oIN5r1CXG4Xup0UiePFz6IRAw/KdzefN1juqgPJbh8dcb0gm', 'siswa', 'nonaktif'),
(17, '0086173300', '$2y$10$M1YTex/xo922QqYtIIE1aeaVKFXka4jAN4LJWJkc2L9knj8bilhai', 'siswa', 'nonaktif'),
(18, '0073023997', '$2y$10$ZnBgOEO6XnNmjtiLbQm2yOfGCbV4vZ/4N3sMlsKjIr6vRgKvFTxTm', 'siswa', 'nonaktif'),
(19, '0089148948', '$2y$10$jzjJYQyqpfVCos4GHwjN7uduoqmX6cpuAflBf7YWqU1Tkduy32SOK', 'siswa', 'nonaktif'),
(34, 'pranatamitra@gmail.com', '$2y$10$.vQkUSbUbAoDOkFnX5OyweuHyH9HrdmEjHPtxpsU9xM3nweDqU1KW', 'guru', 'aktif'),
(35, 'adityapranataa1@gmail.com', '$2y$10$WmbCbVGd3iocZFqNxQ9YD.giYlwso32W3bcHl1B20yHjnrQ7J4XdS', 'guru', 'nonaktif'),
(36, 'donnybangun1210@gmail.com', '$2y$10$y1gumr9Qwob5Ei1DPzzMfOG.YjXmenYQRq.pNGPYVdWA55elpIyp2', 'guru', 'aktif');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `absensi_hari_ini`
--
ALTER TABLE `absensi_hari_ini`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_absen` (`siswa_id`,`tanggal`);

--
-- Indexes for table `bank_soal`
--
ALTER TABLE `bank_soal`
  ADD PRIMARY KEY (`id_soal`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_mapel` (`id_mapel`),
  ADD KEY `fk_bank_soal_kelas` (`id_kelas`);

--
-- Indexes for table `guru`
--
ALTER TABLE `guru`
  ADD PRIMARY KEY (`id_user`);

--
-- Indexes for table `hasil_ujian`
--
ALTER TABLE `hasil_ujian`
  ADD PRIMARY KEY (`id_hasil`),
  ADD KEY `fk_ujian` (`id_ujian`);

--
-- Indexes for table `jawaban_siswa`
--
ALTER TABLE `jawaban_siswa`
  ADD PRIMARY KEY (`id_jawaban`);

--
-- Indexes for table `jurusan`
--
ALTER TABLE `jurusan`
  ADD PRIMARY KEY (`id_jurusan`);

--
-- Indexes for table `kartu_rfid`
--
ALTER TABLE `kartu_rfid`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uid_rfid` (`uid_rfid`),
  ADD KEY `siswa_id` (`siswa_id`);

--
-- Indexes for table `kelas`
--
ALTER TABLE `kelas`
  ADD PRIMARY KEY (`id_kelas`),
  ADD KEY `fk_kelas_jurusan` (`id_jurusan`),
  ADD KEY `fk_kelas_konsentrasi` (`id_konsentrasi`);

--
-- Indexes for table `konsentrasi`
--
ALTER TABLE `konsentrasi`
  ADD PRIMARY KEY (`id_konsentrasi`),
  ADD KEY `id_jurusan` (`id_jurusan`);

--
-- Indexes for table `log_rfid`
--
ALTER TABLE `log_rfid`
  ADD PRIMARY KEY (`id`),
  ADD KEY `siswa_id` (`siswa_id`);

--
-- Indexes for table `mapel`
--
ALTER TABLE `mapel`
  ADD PRIMARY KEY (`id_mapel`),
  ADD KEY `fk_mapel_jurusan` (`id_jurusan`);

--
-- Indexes for table `nilai`
--
ALTER TABLE `nilai`
  ADD PRIMARY KEY (`id_nilai`),
  ADD KEY `id_siswa` (`id_siswa`),
  ADD KEY `id_sesi` (`id_sesi`);

--
-- Indexes for table `ruang_ujian`
--
ALTER TABLE `ruang_ujian`
  ADD PRIMARY KEY (`id_ruang`);

--
-- Indexes for table `sesi_ujian`
--
ALTER TABLE `sesi_ujian`
  ADD PRIMARY KEY (`id_sesi`),
  ADD KEY `id_kelas` (`id_kelas`);

--
-- Indexes for table `sesi_ujian_kelas`
--
ALTER TABLE `sesi_ujian_kelas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unik_sesi_kelas` (`id_sesi`,`id_kelas`),
  ADD KEY `idx_id_sesi` (`id_sesi`),
  ADD KEY `idx_id_kelas` (`id_kelas`);

--
-- Indexes for table `siswa`
--
ALTER TABLE `siswa`
  ADD PRIMARY KEY (`id_siswa`),
  ADD UNIQUE KEY `nisn` (`nisn`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_kelas` (`id_kelas`),
  ADD KEY `fk_ruang` (`id_ruang`);

--
-- Indexes for table `ujian`
--
ALTER TABLE `ujian`
  ADD PRIMARY KEY (`id_ujian`),
  ADD KEY `fk_user` (`id_user`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `absensi_hari_ini`
--
ALTER TABLE `absensi_hari_ini`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `bank_soal`
--
ALTER TABLE `bank_soal`
  MODIFY `id_soal` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=411;

--
-- AUTO_INCREMENT for table `hasil_ujian`
--
ALTER TABLE `hasil_ujian`
  MODIFY `id_hasil` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jawaban_siswa`
--
ALTER TABLE `jawaban_siswa`
  MODIFY `id_jawaban` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=425;

--
-- AUTO_INCREMENT for table `jurusan`
--
ALTER TABLE `jurusan`
  MODIFY `id_jurusan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `kartu_rfid`
--
ALTER TABLE `kartu_rfid`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `kelas`
--
ALTER TABLE `kelas`
  MODIFY `id_kelas` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `konsentrasi`
--
ALTER TABLE `konsentrasi`
  MODIFY `id_konsentrasi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `log_rfid`
--
ALTER TABLE `log_rfid`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `mapel`
--
ALTER TABLE `mapel`
  MODIFY `id_mapel` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `nilai`
--
ALTER TABLE `nilai`
  MODIFY `id_nilai` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ruang_ujian`
--
ALTER TABLE `ruang_ujian`
  MODIFY `id_ruang` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `sesi_ujian`
--
ALTER TABLE `sesi_ujian`
  MODIFY `id_sesi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `sesi_ujian_kelas`
--
ALTER TABLE `sesi_ujian_kelas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=184;

--
-- AUTO_INCREMENT for table `siswa`
--
ALTER TABLE `siswa`
  MODIFY `id_siswa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `ujian`
--
ALTER TABLE `ujian`
  MODIFY `id_ujian` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `absensi_hari_ini`
--
ALTER TABLE `absensi_hari_ini`
  ADD CONSTRAINT `absensi_hari_ini_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id_siswa`) ON DELETE CASCADE;

--
-- Constraints for table `bank_soal`
--
ALTER TABLE `bank_soal`
  ADD CONSTRAINT `fk_bank_soal_kelas` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_bank_soal_mapel` FOREIGN KEY (`id_mapel`) REFERENCES `mapel` (`id_mapel`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_bank_soal_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE SET NULL;

--
-- Constraints for table `hasil_ujian`
--
ALTER TABLE `hasil_ujian`
  ADD CONSTRAINT `fk_hasil_ujian` FOREIGN KEY (`id_ujian`) REFERENCES `ujian` (`id_ujian`) ON DELETE CASCADE;

--
-- Constraints for table `kartu_rfid`
--
ALTER TABLE `kartu_rfid`
  ADD CONSTRAINT `kartu_rfid_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id_siswa`) ON DELETE CASCADE;

--
-- Constraints for table `kelas`
--
ALTER TABLE `kelas`
  ADD CONSTRAINT `fk_kelas_jurusan` FOREIGN KEY (`id_jurusan`) REFERENCES `jurusan` (`id_jurusan`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_kelas_konsentrasi` FOREIGN KEY (`id_konsentrasi`) REFERENCES `konsentrasi` (`id_konsentrasi`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `konsentrasi`
--
ALTER TABLE `konsentrasi`
  ADD CONSTRAINT `konsentrasi_ibfk_1` FOREIGN KEY (`id_jurusan`) REFERENCES `jurusan` (`id_jurusan`) ON UPDATE CASCADE;

--
-- Constraints for table `log_rfid`
--
ALTER TABLE `log_rfid`
  ADD CONSTRAINT `log_rfid_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id_siswa`) ON DELETE CASCADE;

--
-- Constraints for table `mapel`
--
ALTER TABLE `mapel`
  ADD CONSTRAINT `fk_mapel_jurusan` FOREIGN KEY (`id_jurusan`) REFERENCES `jurusan` (`id_jurusan`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `nilai`
--
ALTER TABLE `nilai`
  ADD CONSTRAINT `nilai_ibfk_1` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`) ON DELETE CASCADE,
  ADD CONSTRAINT `nilai_ibfk_2` FOREIGN KEY (`id_sesi`) REFERENCES `sesi_ujian` (`id_sesi`) ON DELETE CASCADE;

--
-- Constraints for table `sesi_ujian`
--
ALTER TABLE `sesi_ujian`
  ADD CONSTRAINT `sesi_ujian_ibfk_1` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`) ON DELETE CASCADE;

--
-- Constraints for table `siswa`
--
ALTER TABLE `siswa`
  ADD CONSTRAINT `fk_ruang` FOREIGN KEY (`id_ruang`) REFERENCES `ruang_ujian` (`id_ruang`) ON DELETE SET NULL,
  ADD CONSTRAINT `siswa_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `siswa_ibfk_2` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`) ON DELETE SET NULL;

--
-- Constraints for table `ujian`
--
ALTER TABLE `ujian`
  ADD CONSTRAINT `fk_ujian_guru` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

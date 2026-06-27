from pathlib import Path
from shutil import copyfile

from reportlab.lib import colors
from reportlab.lib.enums import TA_CENTER, TA_LEFT
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
from reportlab.lib.units import cm
from reportlab.platypus import (
    Flowable,
    Image,
    KeepTogether,
    ListFlowable,
    ListItem,
    PageBreak,
    Paragraph,
    SimpleDocTemplate,
    Spacer,
    Table,
    TableStyle,
)


ROOT = Path(__file__).resolve().parents[1]
OUTPUT = ROOT / "public" / "pdf" / "manual-book-v-hire-user.pdf"
LEGACY_OUTPUT = ROOT / "public" / "pdf" / "MANUAL BOOK V-HIRE (1).pdf"
LOGO = ROOT / "public" / "img" / "logo-vdni1.png"
EXAMPLE_KTP = ROOT / "public" / "img" / "example-ktp.jpg"
EXAMPLE_SIM = ROOT / "public" / "img" / "example-sim_b2.png"

BLUE = colors.HexColor("#0d6efd")
BLUE_DARK = colors.HexColor("#0b3d91")
BLUE_SOFT = colors.HexColor("#eaf3ff")
GREEN = colors.HexColor("#198754")
ORANGE = colors.HexColor("#f59e0b")
RED = colors.HexColor("#dc3545")
INK = colors.HexColor("#172033")
MUTED = colors.HexColor("#5f6b7a")
LINE = colors.HexColor("#d8e2ef")
SOFT = colors.HexColor("#f6f9fc")


class LineRule(Flowable):
    def __init__(self, width=16.6 * cm, color=LINE, thickness=0.8):
        super().__init__()
        self.width = width
        self.height = thickness + 2
        self.color = color
        self.thickness = thickness

    def draw(self):
        self.canv.setStrokeColor(self.color)
        self.canv.setLineWidth(self.thickness)
        self.canv.line(0, 1, self.width, 1)


def make_styles():
    styles = getSampleStyleSheet()
    styles.add(
        ParagraphStyle(
            name="CoverTitle",
            parent=styles["Title"],
            fontName="Helvetica-Bold",
            fontSize=30,
            leading=36,
            textColor=colors.white,
            alignment=TA_CENTER,
            spaceAfter=8,
        )
    )
    styles.add(
        ParagraphStyle(
            name="CoverSubtitle",
            parent=styles["Normal"],
            fontName="Helvetica",
            fontSize=13,
            leading=18,
            textColor=colors.white,
            alignment=TA_CENTER,
        )
    )
    styles.add(
        ParagraphStyle(
            name="H1Manual",
            parent=styles["Heading1"],
            fontName="Helvetica-Bold",
            fontSize=18,
            leading=23,
            textColor=BLUE_DARK,
            spaceBefore=12,
            spaceAfter=8,
        )
    )
    styles.add(
        ParagraphStyle(
            name="H2Manual",
            parent=styles["Heading2"],
            fontName="Helvetica-Bold",
            fontSize=12.5,
            leading=16,
            textColor=INK,
            spaceBefore=8,
            spaceAfter=5,
        )
    )
    styles.add(
        ParagraphStyle(
            name="BodyManual",
            parent=styles["BodyText"],
            fontName="Helvetica",
            fontSize=9.8,
            leading=14.4,
            textColor=INK,
            spaceAfter=6,
        )
    )
    styles.add(
        ParagraphStyle(
            name="SmallManual",
            parent=styles["BodyText"],
            fontName="Helvetica",
            fontSize=8.4,
            leading=12,
            textColor=MUTED,
            spaceAfter=3,
        )
    )
    styles.add(
        ParagraphStyle(
            name="CardTitle",
            parent=styles["BodyText"],
            fontName="Helvetica-Bold",
            fontSize=10.5,
            leading=13.5,
            textColor=INK,
            spaceAfter=3,
        )
    )
    styles.add(
        ParagraphStyle(
            name="CardText",
            parent=styles["BodyText"],
            fontName="Helvetica",
            fontSize=8.8,
            leading=12.5,
            textColor=INK,
        )
    )
    styles.add(
        ParagraphStyle(
            name="TableHead",
            parent=styles["BodyText"],
            fontName="Helvetica-Bold",
            fontSize=8.7,
            leading=11,
            textColor=colors.white,
            alignment=TA_LEFT,
        )
    )
    styles.add(
        ParagraphStyle(
            name="TableBody",
            parent=styles["BodyText"],
            fontName="Helvetica",
            fontSize=8.2,
            leading=11,
            textColor=INK,
        )
    )
    return styles


styles = make_styles()


def p(text, style="BodyManual"):
    return Paragraph(text, styles[style])


def bullets(items, font_size=9.2):
    style = ParagraphStyle(
        name=f"Bullet{font_size}",
        parent=styles["BodyManual"],
        fontSize=font_size,
        leading=13,
        leftIndent=0,
        bulletIndent=0,
        spaceAfter=2,
    )
    return ListFlowable(
        [ListItem(Paragraph(item, style), leftIndent=12) for item in items],
        bulletType="bullet",
        start="circle",
        leftIndent=10,
        bulletFontName="Helvetica",
        bulletFontSize=7,
    )


def section(title):
    return [Spacer(1, 4), p(title, "H1Manual"), LineRule(), Spacer(1, 8)]


def callout(title, body, color=BLUE, background=BLUE_SOFT):
    data = [[p(title, "CardTitle")], [p(body, "CardText")]]
    table = Table(data, colWidths=[16.6 * cm])
    table.setStyle(
        TableStyle(
            [
                ("BACKGROUND", (0, 0), (-1, -1), background),
                ("BOX", (0, 0), (-1, -1), 0.8, color),
                ("LEFTPADDING", (0, 0), (-1, -1), 10),
                ("RIGHTPADDING", (0, 0), (-1, -1), 10),
                ("TOPPADDING", (0, 0), (-1, -1), 7),
                ("BOTTOMPADDING", (0, 0), (-1, -1), 7),
            ]
        )
    )
    return table


def card_grid(cards, columns=2):
    rows = []
    for index in range(0, len(cards), columns):
        row = []
        for title, body, color in cards[index : index + columns]:
            row.append(
                [
                    p(title, "CardTitle"),
                    Paragraph(body, styles["CardText"]),
                ]
            )
        while len(row) < columns:
            row.append("")
        rows.append(row)

    table = Table(rows, colWidths=[8.05 * cm] * columns, hAlign="LEFT")
    commands = [
        ("VALIGN", (0, 0), (-1, -1), "TOP"),
        ("LEFTPADDING", (0, 0), (-1, -1), 9),
        ("RIGHTPADDING", (0, 0), (-1, -1), 9),
        ("TOPPADDING", (0, 0), (-1, -1), 8),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 8),
        ("GRID", (0, 0), (-1, -1), 0.5, LINE),
        ("BACKGROUND", (0, 0), (-1, -1), colors.white),
    ]
    for row_index, row_cards in enumerate(rows):
        for col_index, item in enumerate(row_cards):
            if item:
                commands.append(("LINEBEFORE", (col_index, row_index), (col_index, row_index), 4, cards[row_index * columns + col_index][2]))
    table.setStyle(TableStyle(commands))
    return table


def table_data(headers, rows, widths):
    data = [[p(header, "TableHead") for header in headers]]
    data.extend([[p(str(cell), "TableBody") for cell in row] for row in rows])
    table = Table(data, colWidths=widths, repeatRows=1, hAlign="LEFT")
    table.setStyle(
        TableStyle(
            [
                ("BACKGROUND", (0, 0), (-1, 0), BLUE_DARK),
                ("TEXTCOLOR", (0, 0), (-1, 0), colors.white),
                ("GRID", (0, 0), (-1, -1), 0.45, LINE),
                ("VALIGN", (0, 0), (-1, -1), "TOP"),
                ("LEFTPADDING", (0, 0), (-1, -1), 6),
                ("RIGHTPADDING", (0, 0), (-1, -1), 6),
                ("TOPPADDING", (0, 0), (-1, -1), 5),
                ("BOTTOMPADDING", (0, 0), (-1, -1), 5),
                ("ROWBACKGROUNDS", (0, 1), (-1, -1), [colors.white, SOFT]),
            ]
        )
    )
    return table


def header_footer(canvas, doc):
    canvas.saveState()
    canvas.setFillColor(BLUE_DARK)
    canvas.rect(0, A4[1] - 0.75 * cm, A4[0], 0.75 * cm, fill=1, stroke=0)
    canvas.setFillColor(colors.white)
    canvas.setFont("Helvetica-Bold", 8)
    canvas.drawString(1.45 * cm, A4[1] - 0.48 * cm, "Manual Book V-HIRE - Pelamar")
    canvas.setFont("Helvetica", 8)
    canvas.drawRightString(A4[0] - 1.45 * cm, A4[1] - 0.48 * cm, "https://recruitment.vdnisite.com/")
    canvas.setFillColor(MUTED)
    canvas.setFont("Helvetica", 8)
    canvas.drawString(1.45 * cm, 0.95 * cm, "PT Virtue Dragon Nickel Industry")
    canvas.drawRightString(A4[0] - 1.45 * cm, 0.95 * cm, f"Halaman {doc.page}")
    canvas.restoreState()


def cover_page(canvas, doc):
    canvas.saveState()
    canvas.setFillColor(BLUE_DARK)
    canvas.rect(0, 0, A4[0], A4[1], fill=1, stroke=0)
    canvas.setFillColor(BLUE)
    canvas.rect(0, 0, A4[0], 7.5 * cm, fill=1, stroke=0)
    canvas.setFillColor(colors.white)
    canvas.setFont("Helvetica-Bold", 10)
    canvas.drawCentredString(A4[0] / 2, A4[1] - 3.0 * cm, "PT VIRTUE DRAGON NICKEL INDUSTRY")
    if LOGO.exists():
        canvas.drawImage(str(LOGO), A4[0] / 2 - 2.2 * cm, A4[1] - 6.0 * cm, width=4.4 * cm, height=1.35 * cm, mask="auto")
    canvas.setFont("Helvetica-Bold", 31)
    canvas.drawCentredString(A4[0] / 2, A4[1] - 8.2 * cm, "MANUAL BOOK")
    canvas.setFont("Helvetica-Bold", 24)
    canvas.drawCentredString(A4[0] / 2, A4[1] - 9.4 * cm, "V-HIRE")
    canvas.setFont("Helvetica", 13)
    canvas.drawCentredString(A4[0] / 2, A4[1] - 10.55 * cm, "Panduan Pengguna untuk Pelamar")
    canvas.setFont("Helvetica-Bold", 11)
    canvas.drawCentredString(A4[0] / 2, A4[1] - 12.0 * cm, "Link resmi: https://recruitment.vdnisite.com/")
    canvas.setFont("Helvetica", 9.5)
    canvas.drawCentredString(A4[0] / 2, 4.1 * cm, "Versi pembaruan: Mei 2026")
    canvas.drawCentredString(A4[0] / 2, 3.45 * cm, "HR PT VDNI - Rekrutmen Online")
    canvas.setFont("Helvetica-Bold", 9.5)
    canvas.drawCentredString(A4[0] / 2, 2.4 * cm, "Rekrutmen PT VDNI tidak memungut biaya dalam bentuk apa pun.")
    canvas.restoreState()


def build_story():
    story = [Spacer(1, 25.5 * cm), PageBreak()]

    story += section("Daftar Isi")
    toc_rows = [
        ("1", "Sebelum Mulai", "Hal yang harus disiapkan sebelum membuat akun."),
        ("2", "Alur Singkat", "Urutan proses dari daftar akun sampai memantau lamaran."),
        ("3", "Daftar Akun dan Verifikasi Email", "Cara membuat akun, aktivasi email, dan kirim ulang verifikasi."),
        ("4", "Masuk dan Pemulihan Akun", "Cara login dan memakai menu lupa akun."),
        ("5", "Lengkapi Biodata dan Upload Berkas", "Enam langkah data pelamar dan dokumen yang dibutuhkan."),
        ("6", "Melamar Lowongan", "Cara memilih posisi dan mengirim lamaran."),
        ("7", "Cek Status Lamaran", "Cara membaca status dan detail riwayat seleksi."),
        ("8", "Pengumuman, Kontrak, dan Kelola Akun", "Informasi lanjutan setelah proses lamaran."),
        ("9", "Bantuan dan Kendala Umum", "Solusi untuk kendala dokumen, email, dan akses akun."),
    ]
    story.append(table_data(["No", "Bagian", "Isi"], toc_rows, [1.2 * cm, 5.2 * cm, 10.2 * cm]))
    story.append(Spacer(1, 10))
    story.append(
        callout(
            "Catatan penting",
            "Membuat akun belum berarti Anda sudah melamar pekerjaan. Lamaran baru tercatat setelah Anda melengkapi biodata, mengunggah dokumen, memilih lowongan, lalu menekan tombol Lamar.",
            ORANGE,
            colors.HexColor("#fff8e8"),
        )
    )

    story += section("1. Sebelum Mulai")
    story.append(
        p(
            "Gunakan panduan ini saat mengakses V-HIRE melalui komputer atau ponsel. Semua proses resmi dilakukan melalui website production berikut:"
        )
    )
    story.append(callout("Website resmi", "https://recruitment.vdnisite.com/", BLUE, BLUE_SOFT))
    story.append(Spacer(1, 8))
    story.append(
        card_grid(
            [
                ("Email aktif", "Pastikan email bisa dibuka karena tautan verifikasi akun dikirim melalui email.", BLUE),
                ("NIK/KTP 16 digit", "Siapkan NIK sesuai KTP. NIK harus valid dan belum pernah terdaftar.", GREEN),
                ("Nomor HP aktif", "Gunakan nomor yang mudah dihubungi untuk kebutuhan proses seleksi.", ORANGE),
                ("Dokumen digital", "Siapkan file PDF, JPG, JPEG, atau PNG sesuai ketentuan ukuran di bagian dokumen.", BLUE_DARK),
            ]
        )
    )
    story.append(Spacer(1, 8))
    story.append(
        callout(
            "Waspada penipuan",
            "PT VDNI tidak pernah meminta biaya rekrutmen, biaya transportasi, biaya medical check-up, atau pembayaran lain. Abaikan pihak yang mengaku mewakili perusahaan tetapi meminta uang atau mengarahkan ke website selain recruitment.vdnisite.com.",
            RED,
            colors.HexColor("#fff1f2"),
        )
    )

    story += section("2. Alur Singkat Penggunaan V-HIRE")
    flow = [
        ("1. Buka website", "Kunjungi https://recruitment.vdnisite.com/."),
        ("2. Daftar akun", "Isi NIK, nama, email, kata sandi, dan konfirmasi kata sandi."),
        ("3. Verifikasi email", "Buka email dan klik tautan verifikasi sebelum batas waktu berakhir."),
        ("4. Masuk", "Login memakai email dan kata sandi yang sudah dibuat."),
        ("5. Upload Berkas", "Lengkapi biodata dan unggah semua dokumen yang diperlukan."),
        ("6. Pilih lowongan", "Buka Daftar Lowongan Kerja dan baca kualifikasi posisi."),
        ("7. Kirim lamaran", "Klik Lamar Posisi Ini, lalu konfirmasi Ya, Lamar Sekarang."),
        ("8. Pantau status", "Buka Riwayat Proses Lamaran untuk melihat perkembangan seleksi."),
    ]
    story.append(card_grid([(title, body, BLUE if index % 2 == 0 else GREEN) for index, (title, body) in enumerate(flow)], columns=2))
    story.append(Spacer(1, 8))
    story.append(
        callout(
            "Satu lamaran aktif",
            "Pelamar tidak dapat mengajukan beberapa posisi secara bersamaan. Tunggu proses lamaran yang sedang berjalan selesai sebelum melamar posisi lain.",
            ORANGE,
            colors.HexColor("#fff8e8"),
        )
    )

    story += section("3. Daftar Akun dan Verifikasi Email")
    story.append(p("Langkah membuat akun baru:"))
    story.append(
        bullets(
            [
                "Buka website resmi, lalu klik tombol Daftar.",
                "Isi No KTP/NIK 16 digit, Nama Lengkap sesuai KTP, Email aktif, Kata Sandi, dan Konfirmasi Kata Sandi.",
                "Pastikan email tidak salah ketik karena tautan verifikasi dikirim ke alamat tersebut.",
                "Klik Buat Akun dan tunggu pesan bahwa pendaftaran berhasil.",
            ]
        )
    )
    story.append(Spacer(1, 6))
    story.append(p("Setelah akun dibuat, lakukan verifikasi email:"))
    story.append(
        table_data(
            ["Kondisi", "Yang harus dilakukan"],
            [
                ("Email verifikasi masuk", "Buka email dari sistem V-HIRE, lalu klik tautan verifikasi terbaru."),
                ("Email belum masuk", "Cek folder Spam/Junk/Promotions. Jika tetap belum ada, gunakan menu kirim ulang email verifikasi."),
                ("Melewati batas waktu", "Akun yang belum diverifikasi dapat dihapus otomatis setelah 1 jam. Jika sudah terhapus, daftar ulang dengan NIK dan email yang sama."),
                ("Butuh kirim ulang", "Kirim ulang tersedia setiap 15 menit dan maksimal 2 kali per hari."),
            ],
            [5.0 * cm, 11.6 * cm],
        )
    )

    story += section("4. Masuk dan Pemulihan Akun")
    story.append(p("Untuk masuk ke akun, klik Masuk lalu isi Email dan Kata Sandi. Centang Ingat Saya hanya jika perangkat yang digunakan adalah perangkat pribadi."))
    story.append(
        card_grid(
            [
                ("Belum verifikasi email", "Gunakan tautan Kirim ulang di sini pada halaman login, lalu masukkan email akun.", ORANGE),
                ("Lupa akun atau kata sandi", "Klik Lupa Akun, ikuti instruksi sistem, lalu gunakan tautan reset atau pemulihan yang dikirim.", BLUE),
                ("Gagal login", "Periksa kembali email, kata sandi, dan status verifikasi. Pastikan tidak ada spasi di awal atau akhir email.", RED),
                ("Panduan cepat", "Pada halaman login tersedia Tampilkan Panduan untuk membuka tutorial singkat penggunaan V-HIRE.", GREEN),
            ]
        )
    )

    story += section("5. Lengkapi Biodata dan Upload Berkas")
    story.append(
        p(
            "Setelah login, buka menu Upload Berkas. Form biodata berbentuk wizard 6 langkah. Menu Daftar Lowongan Kerja dan Riwayat Proses Lamaran baru dapat digunakan setelah biodata serta dokumen selesai."
        )
    )
    story.append(
        table_data(
            ["Langkah", "Isi yang perlu dilengkapi", "Catatan"],
            [
                ("01 Data Pribadi", "Nomor HP, No KK, NPWP, jenis kelamin, tempat/tanggal lahir, agama, vaksin, alamat, hobi, golongan darah, tinggi, berat.", "No KK tidak boleh sama dengan No KTP. Isi alamat sesuai data yang benar."),
                ("02 Pendidikan", "Pendidikan terakhir, nama sekolah/kampus, jurusan, nilai/IPK, tahun lulus, dan prestasi jika ada.", "Pastikan pendidikan sesuai kualifikasi lowongan."),
                ("03 Data Keluarga", "Nama ayah, nama ibu, status pernikahan, dan data pasangan/anak jika sudah menikah.", "Jika status Kawin, data pasangan wajib diisi."),
                ("04 Kontak Darurat", "Nama kontak darurat, nomor telepon, dan hubungan keluarga/relasi.", "Gunakan kontak yang mudah dihubungi."),
                ("05 Dokumen Pribadi", "Unggah seluruh dokumen sesuai format dan ukuran.", "KTP dan SIM B2 akan dibaca sistem; gunakan foto yang jelas."),
                ("06 Syarat dan Ketentuan", "Baca dokumen sampai akhir, centang persetujuan, lalu klik Ajukan.", "Tombol Ajukan aktif setelah persetujuan dibaca dan dicentang."),
            ],
            [3.0 * cm, 8.1 * cm, 5.5 * cm],
        )
    )
    story.append(Spacer(1, 8))
    story.append(
        callout(
            "Penyimpanan bertahap",
            "Saat berpindah dari langkah Kontak Darurat ke Dokumen Pribadi, data langkah 1 sampai 4 disimpan terlebih dahulu. Jika ada kolom wajib yang belum benar, sistem akan menandai kolom tersebut.",
            BLUE,
            BLUE_SOFT,
        )
    )

    story.append(PageBreak())
    story += section("Ketentuan Dokumen")
    story.append(
        table_data(
            ["Dokumen", "Format", "Ukuran maksimal", "Keterangan"],
            [
                ("CV", "PDF", "2 MB", "Gunakan CV terbaru."),
                ("Pas Foto 3x4", "JPG/JPEG/PNG", "2 MB", "Foto jelas dan formal."),
                ("Surat Lamaran Kerja", "PDF", "2 MB", "Ditujukan untuk PT VDNI."),
                ("Ijazah dan Transkrip Nilai", "PDF", "2 MB", "Gabungkan dalam satu PDF jika diperlukan."),
                ("KTP", "JPG/JPEG/PNG", "2 MB", "Foto tegak, tidak blur, seluruh data terbaca."),
                ("SIM B II Umum", "JPG/JPEG/PNG", "2 MB", "Wajib bagi pelamar DT/OPR atau posisi yang mensyaratkan."),
                ("SIO", "JPG/JPEG/PNG", "2 MB", "Wajib bagi pelamar DT/OPR atau posisi tertentu."),
                ("SKCK", "PDF", "2 MB", "Gunakan dokumen yang masih berlaku."),
                ("Sertifikat Vaksin", "PDF", "2 MB", "Unggah sertifikat yang tersedia."),
                ("Kartu Keluarga", "PDF", "2 MB", "Pastikan data keluarga terbaca."),
                ("NPWP", "PDF", "2 MB", "Unggah dokumen NPWP."),
                ("Kartu Pencari Kerja AK1", "PDF", "2 MB", "Unggah AK1/Kartu Kuning."),
                ("Sertifikat Pendukung", "PDF", "50 MB", "Jika lebih dari satu, gabungkan dalam satu PDF."),
            ],
            [4.2 * cm, 3.0 * cm, 2.8 * cm, 6.6 * cm],
        )
    )
    story.append(Spacer(1, 8))
    story.append(p("Agar dokumen mudah terbaca sistem dan tim rekrutmen:"))
    story.append(
        bullets(
            [
                "Foto dokumen dari depan dalam posisi tegak, bukan miring atau terbalik.",
                "Pastikan tulisan tidak tertutup jari, stiker, hologram, atau pantulan cahaya.",
                "Gunakan pencahayaan cukup dan hindari gambar yang gelap, blur, atau pecah.",
                "Jangan menambahkan tulisan, coretan, watermark pribadi, atau gambar lain di atas dokumen.",
                "Jika file terlalu besar, kompres file sebelum diunggah tanpa menghilangkan keterbacaan dokumen.",
            ]
        )
    )

    if EXAMPLE_KTP.exists() and EXAMPLE_SIM.exists():
        ktp = Image(str(EXAMPLE_KTP), width=6.8 * cm, height=3.9 * cm)
        sim = Image(str(EXAMPLE_SIM), width=6.8 * cm, height=4.1 * cm)
        image_table = Table(
            [
                [p("Contoh posisi KTP yang baik", "CardTitle"), p("Contoh posisi SIM yang baik", "CardTitle")],
                [ktp, sim],
            ],
            colWidths=[8.1 * cm, 8.1 * cm],
        )
        image_table.setStyle(
            TableStyle(
                [
                    ("GRID", (0, 0), (-1, -1), 0.4, LINE),
                    ("BACKGROUND", (0, 0), (-1, 0), SOFT),
                    ("ALIGN", (0, 1), (-1, 1), "CENTER"),
                    ("VALIGN", (0, 0), (-1, -1), "MIDDLE"),
                    ("TOPPADDING", (0, 0), (-1, -1), 7),
                    ("BOTTOMPADDING", (0, 0), (-1, -1), 7),
                ]
            )
        )
        story.append(Spacer(1, 8))
        story.append(image_table)

    story += section("6. Melamar Lowongan")
    story.append(p("Setelah biodata dan dokumen lengkap, ikuti langkah berikut:"))
    story.append(
        bullets(
            [
                "Buka menu Daftar Lowongan Kerja.",
                "Pilih lowongan yang diminati, lalu klik untuk membuka detail lowongan.",
                "Baca periode lowongan, status pendaftaran, kualifikasi, dan peringatan anti-penipuan.",
                "Jika sudah sesuai, klik Lamar Posisi Ini.",
                "Pada kotak konfirmasi, klik Ya, Lamar Sekarang.",
                "Jika muncul daftar data yang belum lengkap, kembali ke Upload Berkas dan lengkapi bagian yang diminta.",
            ]
        )
    )
    story.append(
        callout(
            "Pastikan sebelum konfirmasi",
            "Periksa kembali posisi yang dipilih. Setelah lamaran masuk, proses seleksi akan mengikuti kebijakan HR dan statusnya dapat dipantau dari menu Riwayat Proses Lamaran.",
            ORANGE,
            colors.HexColor("#fff8e8"),
        )
    )

    story += section("7. Cek Status Lamaran")
    story.append(p("Buka Riwayat Proses Lamaran untuk melihat daftar lamaran yang pernah diajukan. Setiap kartu lamaran menampilkan posisi, tanggal pengajuan, dan status lamaran."))
    story.append(
        table_data(
            ["Status", "Arti", "Yang perlu dilakukan"],
            [
                ("Dalam Proses Rekrutmen", "Lamaran masih berjalan pada tahapan seleksi.", "Buka Baca Detail secara berkala dan pantau email/telepon yang didaftarkan."),
                ("Rekrutmen Selesai", "Proses lamaran untuk posisi tersebut sudah selesai.", "Baca detail proses untuk melihat informasi terakhir dari HR."),
                ("Belum ada riwayat proses", "Belum ada tahapan yang ditambahkan pada lamaran tersebut.", "Cek kembali secara berkala."),
            ],
            [4.1 * cm, 6.0 * cm, 6.5 * cm],
        )
    )
    story.append(Spacer(1, 8))
    story.append(p("Untuk melihat rincian tahapan, klik Baca Detail lalu buka Lihat Detail pada setiap tahapan proses."))

    story += section("8. Pengumuman, Kontrak, dan Kelola Akun")
    story.append(
        card_grid(
            [
                ("Pengumuman", "Menu Pengumuman berisi informasi resmi rekrutmen. Cek halaman ini secara berkala, terutama saat ada jadwal atau hasil proses.", BLUE),
                ("Kelola Akun", "Gunakan menu Kelola Akun untuk melihat atau memperbarui data akun yang tersedia, termasuk kata sandi jika diperlukan.", GREEN),
                ("Kontrak PKWT", "Jika HR mengaktifkan kontrak elektronik untuk akun Anda, buka halaman kontrak yang tersedia, baca isi kontrak, tanda tangani pada area tanda tangan, centang persetujuan, lalu kirim.", ORANGE),
                ("Akun terkunci", "Jika akun tercatat aktif bekerja, biodata, dokumen, dan lamaran baru dapat dikunci untuk mencegah penggunaan ulang akun oleh orang lain.", RED),
            ]
        )
    )

    story += section("9. Bantuan dan Kendala Umum")
    story.append(
        table_data(
            ["Kendala", "Solusi"],
            [
                ("KTP atau SIM B2 tidak terbaca", "Foto ulang dalam posisi tegak, jelas, tidak silau, dan seluruh teks terbaca. Pastikan hologram SIM tidak menutup tulisan penting."),
                ("Tidak bisa melamar", "Pastikan semua langkah Upload Berkas selesai, semua dokumen wajib terunggah, dan syarat ketentuan sudah disetujui."),
                ("Email verifikasi tidak masuk", "Cek Spam/Junk/Promotions. Gunakan menu kirim ulang dan tunggu sesuai batas 15 menit jika baru saja mengirim ulang."),
                ("File gagal diunggah", "Periksa format dan ukuran file. Dokumen umum maksimal 2 MB, sertifikat pendukung maksimal 50 MB."),
                ("Ingin tahu hasil seleksi", "Buka Riwayat Proses Lamaran dan baca detail tahapan. Informasi juga dapat dikirim melalui kontak yang didaftarkan."),
                ("Butuh bantuan teknis", "Hubungi email support: support@vdnisite.com."),
            ],
            [5.0 * cm, 11.6 * cm],
        )
    )
    story.append(Spacer(1, 8))
    story.append(
        callout(
            "Sumber informasi resmi",
            "Gunakan website https://recruitment.vdnisite.com/ dan halaman Bantuan pada V-HIRE. Jangan membagikan kata sandi, kode verifikasi, atau dokumen pribadi kepada pihak yang tidak resmi.",
            BLUE,
            BLUE_SOFT,
        )
    )

    story.append(PageBreak())
    story += section("Checklist Sebelum Klik Lamar")
    checklist = [
        "Saya sudah memakai website resmi https://recruitment.vdnisite.com/.",
        "Akun saya sudah terverifikasi melalui email.",
        "Data pribadi, pendidikan, keluarga, dan kontak darurat sudah benar.",
        "Semua dokumen wajib sudah diunggah dengan format dan ukuran yang sesuai.",
        "KTP/SIM/SIO difoto jelas, tegak, dan dapat dibaca.",
        "Saya sudah membaca kualifikasi lowongan yang dipilih.",
        "Saya paham bahwa PT VDNI tidak memungut biaya rekrutmen.",
        "Saya siap memantau status melalui Riwayat Proses Lamaran.",
    ]
    rows = [(str(index), item) for index, item in enumerate(checklist, 1)]
    story.append(table_data(["No", "Pemeriksaan"], rows, [1.2 * cm, 15.4 * cm]))
    story.append(Spacer(1, 12))
    story.append(p("Dokumen ini diperbarui untuk membantu pelamar memahami alur V-HIRE dengan lebih mudah. Gunakan versi terbaru yang tersedia di halaman Beranda atau Bantuan V-HIRE.", "SmallManual"))

    return story


def main():
    OUTPUT.parent.mkdir(parents=True, exist_ok=True)
    doc = SimpleDocTemplate(
        str(OUTPUT),
        pagesize=A4,
        rightMargin=1.45 * cm,
        leftMargin=1.45 * cm,
        topMargin=1.25 * cm,
        bottomMargin=1.35 * cm,
        title="Manual Book V-HIRE - Panduan Pengguna Pelamar",
        author="HR PT VDNI",
        subject="Panduan penggunaan V-HIRE untuk pelamar",
    )
    doc.build(build_story(), onFirstPage=cover_page, onLaterPages=header_footer)
    copyfile(OUTPUT, LEGACY_OUTPUT)
    print(f"Generated {OUTPUT}")
    print(f"Updated legacy copy {LEGACY_OUTPUT}")


if __name__ == "__main__":
    main()

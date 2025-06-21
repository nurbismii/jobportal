<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>PT VDNI | Rekrutmen Online </title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="rekrutmen" name="keywords">
    <meta content="Situs resmi pendaftaran kerja PT VDNI" name="description">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Inter:slnt,wght@-10..0,100..900&display=swap" rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link rel="stylesheet" href="{{ asset('user/lib/animate/animate.min.css') }}" />
    <link href="{{ asset('user/lib/lightbox/css/lightbox.min.css') }}" rel="stylesheet">
    <link href="{{ asset('user/lib/owlcarousel/assets/owl.carousel.min.css') }}" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="{{ asset('user/css/bootstrap.min.css')}}" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="{{ asset('user/css/style.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    
    <link rel="icon" type="image/x-icon" href="{{ asset('img/logo-title.png') }}">
    @stack('styles')
</head>

<body>

    <!-- Spinner Start -->
    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
    <!-- Spinner End -->

    @include('partials._topbar')

    <!-- Navbar & Hero Start -->
    @include('partials._navbar')
    <!-- Navbar & Hero End -->

    <!-- Modal Search Start -->
    <div class="modal fade" id="searchModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content rounded-0">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Search by keyword</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body d-flex align-items-center bg-primary">
                    <div class="input-group w-75 mx-auto d-flex">
                        <input type="search" class="form-control p-3" placeholder="keywords" aria-describedby="search-icon-1">
                        <span id="search-icon-1" class="btn bg-light border nput-group-text p-3"><i class="fa fa-search"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal Search End -->

    <!-- Content Start -->
    @yield('content')
    <!-- Content End -->

    @include('sweetalert::alert')

    <!-- Footer Start -->
    @include('partials._footer')
    <!-- Footer End -->

    <!-- Copyright Start -->
    <div class="container-fluid copyright py-4">
        <div class="container">
            <div class="row g-4 align-items-center">
                <div class="col-md-6 text-center text-md-end mb-md-0">
                    <span class="text-body"><a href="javascript:void(0)" class="border-bottom text-white"><i class="fas fa-copyright text-light me-2"></i>Rekrutmen Online VDNI</a>, All right reserved.</span>
                </div>
            </div>
        </div>
    </div>
    <!-- Copyright End -->

    <!-- Back to Top -->
    <a href="#" class="btn btn-primary btn-lg-square rounded-circle back-to-top"><i class="fa fa-arrow-up"></i></a>


    <!-- JavaScript Libraries -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('user/lib/wow/wow.min.js') }}"></script>
    <script src="{{ asset('user/lib/easing/easing.min.js') }}"></script>
    <script src="{{ asset('user/lib/waypoints/waypoints.min.js') }}"></script>
    <script src="{{ asset('user/lib/counterup/counterup.min.js') }}"></script>
    <script src="{{ asset('user/lib/lightbox/js/lightbox.min.js') }}"></script>
    <script src="{{ asset('user/lib/owlcarousel/owl.carousel.min.js') }}"></script>

    <!-- Template Javascript -->
    <script src="{{ asset('user/js/main.js') }}"></script>

    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

    <script>
        var morosiCoords = [-3.903640038866699, 122.42485963890647];
        var map = L.map('map').setView(morosiCoords, 15);

        // Tambahkan tampilan satelit dari Esri
        L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: 'Lokasi VDNI'
        }).addTo(map);

        // Tambahkan marker + popup dengan link ke Google Maps
        L.marker(morosiCoords).addTo(map)
            .bindPopup('<b>Site: PT VDNI</b><br><a href="https://www.google.com/maps?q=' + morosiCoords[0] + ',' + morosiCoords[1] + '" target="_blank">Lihat di Google Maps</a>')
            .openPopup();
    </script>
    @stack('scripts')
</body>

</html>
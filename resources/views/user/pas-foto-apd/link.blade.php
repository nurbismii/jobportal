<section class="card {{ $apdBiodata->pas_foto_apd ? '' : 'border-warning' }} mb-4" aria-labelledby="apd-photo-reminder">
    <div class="card-body d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
        <div>
            <h2 id="apd-photo-reminder" class="h5 mb-0">
                {{ $apdBiodata->pas_foto_apd ? 'Pas Foto APD' : 'Perbarui Pas Foto APD' }}
            </h2>
            @unless($apdBiodata->pas_foto_apd)
                <p class="mb-0 mt-2">Unggah foto setengah badan dengan APD dan helm untuk ditampilkan di V-People.</p>
            @endunless
        </div>
        <a href="{{ route('pas-foto-apd.index') }}" class="btn btn-primary flex-shrink-0">
            {{ $apdBiodata->pas_foto_apd ? 'Kelola Pas Foto' : 'Unggah Pas Foto' }}
        </a>
    </div>
</section>

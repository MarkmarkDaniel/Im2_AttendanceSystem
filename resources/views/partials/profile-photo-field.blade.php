{{--
    Profile photo picker for the admin's edit forms.
    Expects $photoUser (the account the photo belongs to). The parent <form>
    must have enctype="multipart/form-data".
--}}
<div class="col-12 mt-4">
    <h2 class="h6 text-uppercase text-muted mb-0">Profile Photo</h2>
</div>

<div class="col-12">
    <div class="d-flex align-items-center gap-3">

        <img src="{{ $photoUser->profileImageUrl() ?? '' }}" alt="Profile photo" id="profilePhotoPreview"
             class="rounded-circle border {{ $photoUser->profile_image ? '' : 'd-none' }}"
             style="width: 84px; height: 84px; object-fit: cover;">
        <div id="profilePhotoPlaceholder"
             class="rounded-circle border bg-light align-items-center justify-content-center text-muted flex-shrink-0 {{ $photoUser->profile_image ? 'd-none' : 'd-flex' }}"
             style="width: 84px; height: 84px;">
            <i class="bi bi-person fs-1"></i>
        </div>

        <div class="flex-grow-1">
            <label for="profile_image" class="form-label">
                {{ $photoUser->profile_image ? 'Replace Profile Photo' : 'Insert Profile Photo' }}
            </label>
            <input type="file" class="form-control @error('profile_image') is-invalid @enderror"
                   id="profile_image" name="profile_image" accept=".jpg,.jpeg,.png,.webp">
            <div class="form-text">JPG, PNG or WEBP. Maximum of 2 MB.</div>
            @error('profile_image')<div class="invalid-feedback">{{ $message }}</div>@enderror

            @if ($photoUser->profile_image)
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" value="1"
                           id="remove_profile_image" name="remove_profile_image">
                    <label class="form-check-label small" for="remove_profile_image">
                        Remove the current photo
                    </label>
                </div>
            @endif
        </div>

    </div>
</div>

@push('scripts')
<script>
    // Show the chosen photo before the form is saved
    document.getElementById('profile_image').addEventListener('change', function () {
        const file = this.files[0];
        if (! file || ! file.type.startsWith('image/')) return;

        const preview     = document.getElementById('profilePhotoPreview');
        const placeholder = document.getElementById('profilePhotoPlaceholder');

        preview.src = URL.createObjectURL(file);
        preview.classList.remove('d-none');
        placeholder.classList.replace('d-flex', 'd-none');
    });
</script>
@endpush

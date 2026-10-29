<script src="{{ asset('public/backEnd/') }}/assets/libs/parsleyjs/parsley.min.js"></script>
<script src="{{ asset('public/backEnd/') }}/assets/js/pages/form-validation.init.js"></script>
<script src="{{ asset('public/backEnd/') }}/assets/libs/select2/js/select2.min.js"></script>
<script src="{{ asset('public/backEnd/') }}/assets/js/pages/form-advanced.init.js"></script>
<script src="{{ asset('public/backEnd/') }}/assets/libs/flatpickr/flatpickr.min.js"></script>
<script src="{{ asset('public/backEnd/') }}/assets/js/pages/form-pickers.init.js"></script>
<script src="{{ asset('public/backEnd/') }}/assets/libs/summernote/summernote-lite.min.js"></script>

<script>
    $(function () {
        $('.select2').select2();

        // ---- Collapsible template sections ----
        $(document).on('click', '.lp-collapsible-head', function (e) {
            if ($(e.target).closest('.lp-section-switch').length) return; // don't collapse when toggling switch
            var card = $(this).closest('.lp-collapsible');
            card.toggleClass('open');
            card.find('.lp-collapsible-body').slideToggle(200);
        });
        // Open all by default (except can collapse manually)
        $('.lp-collapsible').addClass('open').find('.lp-collapsible-body').show();

        // ---- Generic loop rows (add / remove) ----
        $(document).on('click', '.loop-row-add', function () {
            var loop  = $(this).data('loop');
            var title = $(this).data('title');
            var fields = $(this).data('fields') || [];
            var container = $(this).closest('.card-body').find('.loop-rows[data-loop="' + loop + '"]');
            var idx = container.find('.loop-row').length;

            var html = '<div class="loop-row sec-config-card">' +
                '<div class="sec-config-head">' +
                    '<span class="badge-key">' + title + ' #' + (idx + 1) + '</span>' +
                    '<button type="button" class="btn btn-danger btn-sm loop-row-remove"><i class="fa fa-trash"></i></button>' +
                '</div><div class="row">';

            fields.forEach(function (f) {
                var field = f.key || 'field';
                if (f.type === 'textarea') {
                    html += '<div class="col-md-6 mb-2"><label class="form-label small">' + f.label + '</label>' +
                            '<textarea class="form-control form-control-sm" name="' + loop + '[' + idx + '][' + field + ']" placeholder="' + (f.placeholder || '') + '" rows="2"></textarea></div>';
                } else if (f.type === 'file') {
                    html += '<div class="col-md-6 mb-2"><label class="form-label small">' + f.label + '</label>' +
                            '<input type="file" class="form-control form-control-sm" name="' + loop + '[' + idx + '][' + field + ']" data-loop-file="' + loop + '_' + field + '">' +
                            '<input type="hidden" name="' + loop + '[' + idx + '][' + field + '_old]" value="">' +
                            '<img class="img-preview-thumb mt-1" data-loop-preview="' + loop + '_' + field + '" style="display:none;" alt=""></div>';
                } else {
                    html += '<div class="col-md-6 mb-2"><label class="form-label small">' + f.label + '</label>' +
                            '<input type="text" class="form-control form-control-sm" name="' + loop + '[' + idx + '][' + field + ']" value="" placeholder="' + (f.placeholder || '') + '"></div>';
                }
            });

            html += '</div></div>';
            container.find('.loop-empty-hint').remove();
            container.append(html);
        });

        $(document).on('click', '.loop-row-remove', function () {
            $(this).closest('.loop-row').remove();
        });

        // Loop file image preview
        $(document).on('change', '[data-loop-file]', function () {
            var file = this.files && this.files[0];
            if (!file) return;
            var key = $(this).data('loop-file');
            var preview = document.querySelector('[data-loop-preview="' + key + '"]');
            var reader = new FileReader();
            reader.onload = function (e) { if (preview) { preview.src = e.target.result; preview.style.display = 'block'; } };
            reader.readAsDataURL(file);
        });

        // Review images increment
        $(".btn-increment").click(function () {
            var html = $(".clone").html();
            $(".increment").after(html);
        });
        $("body").on("click", ".btn-danger", function () {
            $(this).parents(".control-group").remove();
        });

        // local image thumbnails for file inputs
        $('input[type="file"]').on('change', function () {
            var file = this.files && this.files[0];
            if (!file) return;
            var reader = new FileReader();
            var thumb = document.querySelector('[data-thumb-for="' + this.name + '"]');
            reader.onload = function (e) {
                if (thumb) { thumb.src = e.target.result; thumb.style.display = 'block'; }
            };
            reader.readAsDataURL(file);
        });
    });
</script>

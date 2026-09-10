<div class="modal fade" id="mediaPickerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title"><i class="fas fa-photo-video me-1"></i>{{ __('Choose Media') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body"><div id="mediaPickerContent" class="d-flex flex-wrap gap-2" data-picker-url="{{ route('admin.media.picker') }}">{{ __('Loading...') }}</div></div>
    </div></div>
</div>
<script>
var mediaPickerTarget = null;
var mediaPickerPreview = null;
function openMediaPicker(target, preview) {
    mediaPickerTarget = target;
    mediaPickerPreview = preview || null;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('mediaPickerModal')).show();
}
(function(){
    var box=document.getElementById('mediaPickerContent');
    if(!box)return;
    document.addEventListener('click',function(e){
        var item=e.target.closest('.media-picker-item');
        if(!item)return;
        if(mediaPickerTarget){
            var target=document.querySelector(mediaPickerTarget);
            if(target){target.value=item.dataset.url;target.dispatchEvent(new Event('change',{bubbles:true}));}
        }
        if(mediaPickerPreview){var preview=document.querySelector(mediaPickerPreview);if(preview)preview.src=item.dataset.url;}
        var label=document.querySelector((mediaPickerTarget || '') + '_file');
        if(label)label.textContent=item.dataset.name;
        bootstrap.Modal.getInstance(document.getElementById('mediaPickerModal'))?.hide();
    });
    document.getElementById('mediaPickerModal').addEventListener('shown.bs.modal',function(){
        fetch(box.dataset.pickerUrl).then(function(r){return r.text()}).then(function(html){box.innerHTML=html;});
    });
})();
</script>

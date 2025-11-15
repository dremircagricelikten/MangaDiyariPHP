<div class="modal fade" id="chapter-edit-modal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content bg-dark text-light">
      <form id="chapter-edit-form" enctype="multipart/form-data">
        <div class="modal-header border-secondary">
          <h5 class="modal-title">Bölümü Düzenle</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Kapat"></button>
        </div>
        <div class="modal-body row g-3">
          <input type="hidden" name="id" id="edit-chapter-id">
          <div class="col-md-4">
            <label class="form-label">Bölüm No</label>
            <input type="text" class="form-control" name="number" id="edit-chapter-number" required>
          </div>
          <div class="col-md-8">
            <label class="form-label">Başlık</label>
            <input type="text" class="form-control" name="title" id="edit-chapter-title">
          </div>
          <div class="col-12">
            <label class="form-label">İçerik</label>
            <textarea class="form-control" name="content" id="edit-chapter-content" rows="4"></textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label">Ki Bedeli</label>
            <input type="number" class="form-control" name="ki_cost" id="edit-chapter-ki" min="0">
          </div>
          <div class="col-md-6">
            <label class="form-label">Premium Bitişi</label>
            <input type="datetime-local" class="form-control" name="premium_expires_at" id="edit-chapter-premium">
          </div>
          <div class="col-12">
            <label class="form-label">Yeni ZIP / Görsel</label>
            <input type="file" class="form-control" name="chapter_files[]" id="edit-chapter-files" multiple accept=".zip,image/*">
            <small class="text-muted">Dosya yüklemezseniz mevcut görseller korunur.</small>
          </div>
          <div id="chapter-edit-message" class="col-12"></div>
        </div>
        <div class="modal-footer border-secondary d-flex justify-content-between">
          <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Vazgeç</button>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-danger" id="delete-chapter"><i class="bi bi-trash me-1"></i>Sil</button>
            <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Güncelle</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

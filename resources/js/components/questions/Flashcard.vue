<template>
  <div>
    <!-- Confirm side type change modal -->
    <b-modal
      id="modal-flashcard-confirm-type-change"
      title="Change Type?"
      no-close-on-backdrop
    >
      <p>{{ typeChangeWarningMessage }}</p>
      <template #modal-footer>
        <b-button size="sm" @click="cancelTypeChange">Cancel</b-button>
        <b-button size="sm" variant="danger" @click="confirmTypeChange">Yes, Change Type</b-button>
      </template>
    </b-modal>

    <b-card no-body class="mb-3">
      <b-card-header>
        <strong>Flashcard</strong>
      </b-card-header>
      <b-card-body>

        <!-- ── FRONT PANEL ───────────────────────────────────────── -->
        <div class="flashcard-side-label">Front</div>

        <!-- Front type selector -->
        <b-form-radio-group
          v-model="pendingFrontType"
          name="flashcard-front-type"
          class="mb-2"
          @change="handleTypeChange('front', $event)"
        >
          <b-form-radio value="text_only">Text Only</b-form-radio>
          <b-form-radio value="text_media">Text &amp; Media</b-form-radio>
          <b-form-radio value="free_form">Free-form</b-form-radio>
          <b-form-radio value="media">Media</b-form-radio>
        </b-form-radio-group>

        <div class="flashcard-side-panel flashcard-side-front mb-1">
          <!-- Free-form front -->
          <template v-if="form.frontType === 'free_form'">
            <ckeditor
              v-model="form.front"
              :config="richEditorConfig"
              @namespaceloaded="onCKEditorNamespaceLoaded"
            />
          </template>

          <!-- Text Only front -->
          <template v-else-if="form.frontType === 'text_only'">
            <textarea
              ref="termInput"
              class="flashcard-fill"
              :value="form.term"
              @input="form.term = $event.target.value; errors.term = ''"
              @blur="form.term = $event.target.value"
            />
          </template>

          <!-- Text & Media front -->
          <template v-else-if="form.frontType === 'text_media'">
            <div class="flashcard-two-col-editor">
              <div class="flashcard-editor-term">
                <textarea
                  ref="termInput"
                  class="flashcard-fill"
                  :value="form.term"
                  @input="form.term = $event.target.value; errors.term = ''"
                  @blur="form.term = $event.target.value"
                />
              </div>
              <div class="flashcard-col-divider"/>
              <div class="flashcard-editor-media">
                <div
                  class="flashcard-drop-zone"
                  :class="{ 'drop-zone-active': isDraggingFront, 'drop-zone-has-file': !!(form.frontImageUrl || form.frontVideoUrl) }"
                  @dragenter.prevent="isDraggingFront = true"
                  @dragover.prevent="isDraggingFront = true"
                  @dragleave.prevent="isDraggingFront = false"
                  @drop.prevent="handleDrop($event, 'front')"
                  @click="$refs.frontMediaInput.click()"
                >
                  <div v-if="form.frontImageUrl" class="drop-zone-preview">
                    <img :src="form.frontImageUrl" alt="Uploaded image" class="preview-img"/>
                    <b-button size="sm" variant="outline-danger" class="mt-2" @click.stop="clearMedia('front')">Remove</b-button>
                  </div>
                  <div v-else-if="form.frontVideoUrl" class="drop-zone-preview">
                    <video :src="form.frontVideoUrl" controls class="preview-video" @click.stop/>
                    <b-button size="sm" variant="outline-danger" class="mt-2" @click.stop="clearMedia('front')">Remove</b-button>
                  </div>
                  <div v-else class="drop-zone-placeholder">
                    <div>Drag &amp; drop an image or video here</div>
                    <div class="drop-zone-or">or</div>
                    <b-button size="sm" variant="outline-secondary" @click.stop="$refs.frontMediaInput.click()">Browse Files</b-button>
                    <div class="drop-zone-hint">JPG, PNG, GIF, WebP, MP4, MOV, WebM</div>
                  </div>
                </div>
                <input ref="frontMediaInput" type="file" accept=".jpg,.jpeg,.png,.gif,.webp,.mp4,.mov,.webm" style="display:none" @change="handleFileSelect($event, 'front')"/>
                <div v-if="form.frontImageUrl" class="mt-2">
                  <b-form-input
                    v-model="form.frontMediaAlt"
                    size="sm"
                    placeholder="Alt text (required for accessibility)"
                    :state="errors.frontMediaAlt ? false : null"
                    @input="errors.frontMediaAlt = ''"
                  />
                  <div v-if="errors.frontMediaAlt" class="invalid-feedback d-block">{{ errors.frontMediaAlt }}</div>
                </div>
              </div>
            </div>
          </template>

          <!-- Media only front -->
          <template v-else-if="form.frontType === 'media'">
            <div class="flashcard-media-only">
              <div
                class="flashcard-drop-zone"
                :class="{ 'drop-zone-active': isDraggingFront, 'drop-zone-has-file': !!(form.frontImageUrl || form.frontVideoUrl) }"
                @dragenter.prevent="isDraggingFront = true"
                @dragover.prevent="isDraggingFront = true"
                @dragleave.prevent="isDraggingFront = false"
                @drop.prevent="handleDrop($event, 'front')"
                @click="$refs.frontMediaInput.click()"
              >
                <div v-if="form.frontImageUrl" class="drop-zone-preview">
                  <img :src="form.frontImageUrl" alt="Uploaded image" class="preview-img"/>
                  <b-button size="sm" variant="outline-danger" class="mt-2" @click.stop="clearMedia('front')">Remove</b-button>
                </div>
                <div v-else-if="form.frontVideoUrl" class="drop-zone-preview">
                  <video :src="form.frontVideoUrl" controls class="preview-video" @click.stop/>
                  <b-button size="sm" variant="outline-danger" class="mt-2" @click.stop="clearMedia('front')">Remove</b-button>
                </div>
                <div v-else class="drop-zone-placeholder">
                  <div>Drag &amp; drop an image or video here</div>
                  <div class="drop-zone-or">or</div>
                  <b-button size="sm" variant="outline-secondary" @click.stop="$refs.frontMediaInput.click()">Browse Files</b-button>
                  <div class="drop-zone-hint">JPG, PNG, GIF, WebP, MP4, MOV, WebM</div>
                </div>
              </div>
              <input ref="frontMediaInput" type="file" accept=".jpg,.jpeg,.png,.gif,.webp,.mp4,.mov,.webm" style="display:none" @change="handleFileSelect($event, 'front')"/>
              <div v-if="form.frontImageUrl" class="mt-2">
                <b-form-input
                  v-model="form.frontMediaAlt"
                  size="sm"
                  placeholder="Alt text (required for accessibility)"
                  :state="errors.frontMediaAlt ? false : null"
                  @input="errors.frontMediaAlt = ''"
                />
                <div v-if="errors.frontMediaAlt" class="invalid-feedback d-block">{{ errors.frontMediaAlt }}</div>
              </div>
            </div>
          </template>
        </div>

        <!-- Front errors -->
        <div v-if="errors.front" class="invalid-feedback d-block mb-2">{{ errors.front }}</div>
        <div v-if="form.frontType === 'text_media'" class="flashcard-error-row mb-2">
          <div class="flashcard-error-term">
            <div v-if="errors.term" class="invalid-feedback d-block">{{ errors.term }}</div>
          </div>
          <div class="flashcard-error-divider"/>
          <div class="flashcard-error-media">
            <div v-if="errors.frontMediaS3Key" class="invalid-feedback d-block">{{ errors.frontMediaS3Key }}</div>
          </div>
        </div>
        <div v-else-if="errors.term" class="invalid-feedback d-block mb-2">{{ errors.term }}</div>
        <div v-if="form.frontType === 'media' && errors.frontMediaS3Key" class="invalid-feedback d-block mb-2">{{ errors.frontMediaS3Key }}</div>

        <hr>

        <!-- ── BACK PANEL ────────────────────────────────────────── -->
        <div class="flashcard-side-label">Back</div>

        <!-- Back type selector -->
        <b-form-radio-group
          v-model="pendingBackType"
          name="flashcard-back-type"
          class="mb-2"
          @change="handleTypeChange('back', $event)"
        >
          <b-form-radio value="text_only">Text Only</b-form-radio>
          <b-form-radio value="text_media">Text &amp; Media</b-form-radio>
          <b-form-radio value="free_form">Free-form</b-form-radio>
          <b-form-radio value="media">Media</b-form-radio>
        </b-form-radio-group>

        <div class="flashcard-side-panel flashcard-side-back mb-1">
          <!-- Free-form back -->
          <template v-if="form.backType === 'free_form'">
            <ckeditor
              v-model="form.back"
              :config="richEditorConfig"
              @namespaceloaded="onCKEditorNamespaceLoaded"
            />
          </template>

          <!-- Text Only back -->
          <template v-else-if="form.backType === 'text_only'">
            <textarea
              ref="answerInput"
              class="flashcard-fill"
              :value="form.answer"
              @input="form.answer = $event.target.value; errors.answer = ''"
              @blur="form.answer = $event.target.value"
            />
          </template>

          <!-- Text & Media back -->
          <template v-else-if="form.backType === 'text_media'">
            <div class="flashcard-two-col-editor">
              <div class="flashcard-editor-term">
                <textarea
                  ref="answerInput"
                  class="flashcard-fill"
                  :value="form.answer"
                  @input="form.answer = $event.target.value; errors.answer = ''"
                  @blur="form.answer = $event.target.value"
                />
              </div>
              <div class="flashcard-col-divider"/>
              <div class="flashcard-editor-media">
                <div
                  class="flashcard-drop-zone"
                  :class="{ 'drop-zone-active': isDraggingBack, 'drop-zone-has-file': !!(form.backImageUrl || form.backVideoUrl) }"
                  @dragenter.prevent="isDraggingBack = true"
                  @dragover.prevent="isDraggingBack = true"
                  @dragleave.prevent="isDraggingBack = false"
                  @drop.prevent="handleDrop($event, 'back')"
                  @click="$refs.backMediaInput.click()"
                >
                  <div v-if="form.backImageUrl" class="drop-zone-preview">
                    <img :src="form.backImageUrl" alt="Uploaded image" class="preview-img"/>
                    <b-button size="sm" variant="outline-danger" class="mt-2" @click.stop="clearMedia('back')">Remove</b-button>
                  </div>
                  <div v-else-if="form.backVideoUrl" class="drop-zone-preview">
                    <video :src="form.backVideoUrl" controls class="preview-video" @click.stop/>
                    <b-button size="sm" variant="outline-danger" class="mt-2" @click.stop="clearMedia('back')">Remove</b-button>
                  </div>
                  <div v-else class="drop-zone-placeholder">
                    <div>Drag &amp; drop an image or video here</div>
                    <div class="drop-zone-or">or</div>
                    <b-button size="sm" variant="outline-secondary" @click.stop="$refs.backMediaInput.click()">Browse Files</b-button>
                    <div class="drop-zone-hint">JPG, PNG, GIF, WebP, MP4, MOV, WebM</div>
                  </div>
                </div>
                <input ref="backMediaInput" type="file" accept=".jpg,.jpeg,.png,.gif,.webp,.mp4,.mov,.webm" style="display:none" @change="handleFileSelect($event, 'back')"/>
                <div v-if="form.backImageUrl" class="mt-2">
                  <b-form-input
                    v-model="form.backMediaAlt"
                    size="sm"
                    placeholder="Alt text (required for accessibility)"
                    :state="errors.backMediaAlt ? false : null"
                    @input="errors.backMediaAlt = ''"
                  />
                  <div v-if="errors.frontMediaAlt" class="invalid-feedback d-block">{{ errors.backMediaAlt }}</div>
                </div>
              </div>
            </div>
          </template>

          <!-- Media only back -->
          <template v-else-if="form.backType === 'media'">
            <div class="flashcard-media-only">
              <div
                class="flashcard-drop-zone"
                :class="{ 'drop-zone-active': isDraggingBack, 'drop-zone-has-file': !!(form.backImageUrl || form.backVideoUrl) }"
                @dragenter.prevent="isDraggingBack = true"
                @dragover.prevent="isDraggingBack = true"
                @dragleave.prevent="isDraggingBack = false"
                @drop.prevent="handleDrop($event, 'back')"
                @click="$refs.backMediaInput.click()"
              >
                <div v-if="form.backImageUrl" class="drop-zone-preview">
                  <img :src="form.backImageUrl" alt="Uploaded image" class="preview-img"/>
                  <b-button size="sm" variant="outline-danger" class="mt-2" @click.stop="clearMedia('back')">Remove</b-button>
                </div>
                <div v-else-if="form.backVideoUrl" class="drop-zone-preview">
                  <video :src="form.backVideoUrl" controls class="preview-video" @click.stop/>
                  <b-button size="sm" variant="outline-danger" class="mt-2" @click.stop="clearMedia('back')">Remove</b-button>
                </div>
                <div v-else class="drop-zone-placeholder">
                  <div>Drag &amp; drop an image or video here</div>
                  <div class="drop-zone-or">or</div>
                  <b-button size="sm" variant="outline-secondary" @click.stop="$refs.backMediaInput.click()">Browse Files</b-button>
                  <div class="drop-zone-hint">JPG, PNG, GIF, WebP, MP4, MOV, WebM</div>
                </div>
              </div>
              <input ref="backMediaInput" type="file" accept=".jpg,.jpeg,.png,.gif,.webp,.mp4,.mov,.webm" style="display:none" @change="handleFileSelect($event, 'back')"/>
              <div v-if="form.backImageUrl" class="mt-2">
                <b-form-input
                  v-model="form.backMediaAlt"
                  size="sm"
                  placeholder="Alt text (required for accessibility)"
                  :state="errors.backMediaAlt ? false : null"
                  @input="errors.backMediaAlt = ''"
                />
                <div v-if="errors.frontMediaAlt" class="invalid-feedback d-block">{{ errors.backMediaAlt }}</div>
              </div>
            </div>
          </template>
        </div>

        <!-- Back errors -->
        <div v-if="errors.back" class="invalid-feedback d-block mb-2">{{ errors.back }}</div>
        <div v-if="form.backType === 'text_media'" class="flashcard-error-row mb-2">
          <div class="flashcard-error-term">
            <div v-if="errors.answer" class="invalid-feedback d-block">{{ errors.answer }}</div>
          </div>
          <div class="flashcard-error-divider"/>
          <div class="flashcard-error-media">
            <div v-if="errors.backMediaS3Key" class="invalid-feedback d-block">{{ errors.backMediaS3Key }}</div>
          </div>
        </div>
        <div v-else-if="errors.answer" class="invalid-feedback d-block mb-2">{{ errors.answer }}</div>
        <div v-if="form.backType === 'media' && errors.backMediaS3Key" class="invalid-feedback d-block mb-2">{{ errors.backMediaS3Key }}</div>

      </b-card-body>
    </b-card>
  </div>
</template>

<script>
import CKEditor from 'ckeditor4-vue'

const ACCEPTED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp']
const ACCEPTED_VIDEO_TYPES = ['video/mp4', 'video/quicktime', 'video/webm']

function emptyForm (frontType = 'text_only', backType = 'text_only') {
  return {
    frontType,
    backType,
    // front fields
    front: '',
    term: '',
    frontMediaUrl: '',
    frontMediaS3Key: '',
    frontMediaFile: null,
    frontMediaType: null,
    frontMediaAlt: '',
    frontImageUrl: '',
    frontVideoUrl: '',
    // back fields
    back: '',
    answer: '',
    backMediaUrl: '',
    backMediaS3Key: '',
    backMediaFile: null,
    backMediaType: null,
    backMediaAlt: '',
    backImageUrl: '',
    backVideoUrl: ''
  }
}

function needsWarning (side, fromType, toType, form) {
  // free_form → anything warns only if there's content
  if (fromType === 'free_form') {
    const content = side === 'front' ? form.front : form.back
    return !!content
  }
  // anything → media warns if there's content
  if (toType === 'media') return sideHasContent(side, fromType, form)
  // media → anything warns if there's media
  if (fromType === 'media') return !!(form[`${side}MediaUrl`])
  // text_media → text_only or free_form warns only if media exists
  if (fromType === 'text_media' && (toType === 'text_only' || toType === 'free_form')) {
    return !!(form[`${side}MediaUrl`])
  }
  return false
}
function sideHasContent (side, type, form) {
  if (type === 'free_form') return !!(side === 'front' ? form.front : form.back)
  if (type === 'text_only') return !!(side === 'front' ? form.term : form.answer)
  if (type === 'text_media') return !!(side === 'front' ? (form.term || form.frontMediaUrl) : (form.answer || form.backMediaUrl))
  if (type === 'media') return !!(form[`${side}MediaUrl`])
  return false
}

function warningMessage (side, fromType, toType, form) {
  if (fromType === 'free_form') {
    return 'Changing from Free-form will clear all rich text content. Are you sure?'
  }
  if (fromType === 'media') {
    return 'Changing from Media will remove the uploaded file. Are you sure?'
  }
  if (toType === 'media') {
    return 'Changing to Media will clear all existing content for this side. Are you sure?'
  }
  if (fromType === 'text_media' && form[`${side}MediaUrl`]) {
    return 'Changing from Text & Media will remove the uploaded image or video. The text will be kept. Are you sure?'
  }
  return 'Changing the type will clear some content. Are you sure?'
}

export default {
  name: 'Flashcard',

  components: {
    ckeditor: CKEditor.component
  },

  props: {
    initialCard: {
      type: Object,
      default: null
    },
    richEditorConfig: {
      type: Object,
      default: () => ({})
    }
  },

  data () {
    const form = this.initialCard
      ? { ...emptyForm(this.initialCard.frontType || 'text_only', this.initialCard.backType || 'text_only'), ...this.initialCard }
      : emptyForm()

    return {
      form,
      pendingFrontType: form.frontType,
      pendingBackType: form.backType,
      pendingTypeChangeSide: null,
      pendingTypeChangeValue: null,
      typeChangeWarningMessage: '',
      isDraggingFront: false,
      isDraggingBack: false,
      errors: {
        front: '',
        back: '',
        term: '',
        answer: '',
        frontMediaS3Key: '',
        backMediaS3Key: '',
        frontMediaAlt: '',
        backMediaAlt: ''
      }
    }
  },

  mounted () {
    if (this.form.term && this.$refs.termInput) {
      this.$refs.termInput.value = this.form.term
    }
    if (this.form.answer && this.$refs.answerInput) {
      this.$refs.answerInput.value = this.form.answer
    }
    // Hydrate image/video aliases on load
    if (this.form.frontMediaUrl && this.form.frontMediaType) {
      this.form.frontImageUrl = this.form.frontMediaType === 'image' ? this.form.frontMediaUrl : ''
      this.form.frontVideoUrl = this.form.frontMediaType === 'video' ? this.form.frontMediaUrl : ''
    }
    if (this.form.backMediaUrl && this.form.backMediaType) {
      this.form.backImageUrl = this.form.backMediaType === 'image' ? this.form.backMediaUrl : ''
      this.form.backVideoUrl = this.form.backMediaType === 'video' ? this.form.backMediaUrl : ''
    }
  },

  methods: {
    setErrors (errors) {
      this.errors.term = errors.term || ''
      this.errors.answer = errors.answer || ''
      this.errors.front = errors.front || ''
      this.errors.back = errors.back || ''
      this.errors.frontMediaS3Key = errors.frontMediaS3Key || ''
      this.errors.backMediaS3Key = errors.backMediaS3Key || ''
      this.errors.frontMediaAlt = errors.frontMediaAlt || ''
      this.errors.backMediaAlt = errors.backMediaAlt || ''
    },

    handleTypeChange (side, newType) {
      const fromType = side === 'front' ? this.form.frontType : this.form.backType
      if (newType === fromType) return

      if (needsWarning(side, fromType, newType, this.form)) {
        this.typeChangeWarningMessage = warningMessage(side, fromType, newType, this.form)
        this.pendingTypeChangeSide = side
        this.pendingTypeChangeValue = newType
        this.$bvModal.show('modal-flashcard-confirm-type-change')
      } else {
        this.applyTypeChange(side, newType, fromType)
      }
    },

    confirmTypeChange () {
      this.$bvModal.hide('modal-flashcard-confirm-type-change')
      this.applyTypeChange(this.pendingTypeChangeSide, this.pendingTypeChangeValue,
        this.pendingTypeChangeSide === 'front' ? this.form.frontType : this.form.backType)
    },

    cancelTypeChange () {
      // Reset radio to current type
      if (this.pendingTypeChangeSide === 'front') {
        this.pendingFrontType = this.form.frontType
      } else {
        this.pendingBackType = this.form.backType
      }
      this.$bvModal.hide('modal-flashcard-confirm-type-change')
    },

    applyTypeChange (side, newType, fromType) {
      const isFront = side === 'front'

      // Clear media if losing it
      const losesMedia = fromType === 'media' ||
        fromType === 'text_media' ||
        newType === 'media'

      if (losesMedia) {
        this.clearMedia(side)
      }

      // Clear text only if going to/from free_form or to media
      const losesText = fromType === 'free_form' || newType === 'media' || fromType === 'media'

      if (losesText) {
        if (isFront) {
          this.form.front = ''
          this.form.term = ''
        } else {
          this.form.back = ''
          this.form.answer = ''
        }
      }

      // Carry text from text_only/text_media into free_form
      if (newType === 'free_form' && (fromType === 'text_only' || fromType === 'text_media')) {
        if (isFront && this.form.term) this.form.front = this.form.term
        if (!isFront && this.form.answer) this.form.back = this.form.answer
      }

      // Carry text from free_form into text_only/text_media — NOT allowed (warning shown)
      // Carry text between text_only and text_media — text stays, nothing to do

      if (isFront) {
        this.form.frontType = newType
        this.pendingFrontType = newType
      } else {
        this.form.backType = newType
        this.pendingBackType = newType
      }

      this.clearSideErrors(side)

      this.$nextTick(() => {
        if (isFront && this.$refs.termInput) this.$refs.termInput.value = this.form.term || ''
        if (!isFront && this.$refs.answerInput) this.$refs.answerInput.value = this.form.answer || ''
      })
    },

    clearSideErrors (side) {
      if (side === 'front') {
        this.errors.front = ''
        this.errors.term = ''
        this.errors.frontMediaS3Key = ''
        this.errors.frontMediaAlt = ''
      } else {
        this.errors.back = ''
        this.errors.answer = ''
        this.errors.backMediaS3Key = ''
        this.errors.backMediaAlt = ''
      }
    },

    handleDrop (event, side) {
      if (side === 'front') this.isDraggingFront = false
      else this.isDraggingBack = false
      const file = event.dataTransfer.files[0]
      if (file) this.processFile(file, side)
    },

    handleFileSelect (event, side) {
      const file = event.target.files[0]
      if (file) this.processFile(file, side)
      event.target.value = ''
    },

    processFile (file, side) {
      const isImage = ACCEPTED_IMAGE_TYPES.includes(file.type)
      const isVideo = ACCEPTED_VIDEO_TYPES.includes(file.type)

      if (!isImage && !isVideo) {
        this.$noty
          ? this.$noty.error('Please upload a valid file (JPG, PNG, GIF, WebP, MP4, MOV, or WebM).')
          : alert('Please upload a valid file (JPG, PNG, GIF, WebP, MP4, MOV, or WebM).')
        return
      }

      const existingUrl = this.form[`${side}MediaUrl`]
      const existingFile = this.form[`${side}MediaFile`]
      if (existingUrl && existingFile) URL.revokeObjectURL(existingUrl)

      const objectUrl = URL.createObjectURL(file)
      this.form[`${side}MediaUrl`] = objectUrl
      this.form[`${side}MediaFile`] = file
      this.form[`${side}MediaType`] = isImage ? 'image' : 'video'
      this.form[`${side}ImageUrl`] = isImage ? objectUrl : ''
      this.form[`${side}VideoUrl`] = isVideo ? objectUrl : ''
      this.form[`${side}MediaAlt`] = ''
      this.errors[`${side}MediaS3Key`] = ''
      this.errors[`${side}MediaAlt`] = ''

      this.$emit('file-selected', { side, mediaType: this.form[`${side}MediaType`], file })
    },

    clearMedia (side) {
      const existingUrl = this.form[`${side}MediaUrl`]
      const existingFile = this.form[`${side}MediaFile`]
      if (existingUrl && existingFile) URL.revokeObjectURL(existingUrl)
      this.form[`${side}MediaUrl`] = ''
      this.form[`${side}MediaFile`] = null
      this.form[`${side}MediaType`] = null
      this.form[`${side}ImageUrl`] = ''
      this.form[`${side}VideoUrl`] = ''
      this.form[`${side}MediaAlt`] = ''
      this.form[`${side}MediaS3Key`] = ''
    },

    getCardData () {
      const f = this.form
      const data = {
        frontType: f.frontType,
        backType: f.backType
      }

      // Front
      switch (f.frontType) {
        case 'free_form': data.front = f.front; break
        case 'text_only': data.term = f.term; break
        case 'text_media':
          data.term = f.term
          data.frontMediaS3Key = f.frontMediaS3Key
          data.frontMediaType = f.frontMediaType
          data.frontMediaAlt = f.frontMediaAlt
          break
        case 'media':
          data.frontMediaS3Key = f.frontMediaS3Key
          data.frontMediaType = f.frontMediaType
          data.frontMediaAlt = f.frontMediaAlt
          break
      }

      // Back
      switch (f.backType) {
        case 'free_form': data.back = f.back; break
        case 'text_only': data.answer = f.answer; break
        case 'text_media':
          data.answer = f.answer
          data.backMediaS3Key = f.backMediaS3Key
          data.backMediaType = f.backMediaType
          data.backMediaAlt = f.backMediaAlt
          break
        case 'media':
          data.backMediaS3Key = f.backMediaS3Key
          data.backMediaType = f.backMediaType
          data.backMediaAlt = f.backMediaAlt
          break
      }

      return data
    },

    validate () {
      return this.getCardData()
    },

    updateMediaUrl (side, mediaType, s3Key, temporaryUrl) {
      this.form[`${side}MediaUrl`] = temporaryUrl
      this.form[`${side}MediaS3Key`] = s3Key
      this.form[`${side}MediaType`] = mediaType
      this.form[`${side}ImageUrl`] = mediaType === 'image' ? temporaryUrl : ''
      this.form[`${side}VideoUrl`] = mediaType === 'video' ? temporaryUrl : ''
    },

    onCKEditorNamespaceLoaded (CKEDITOR) {
      CKEDITOR.addCss('.cke_editable { font-size: 15px; }')
    }
  },

  beforeDestroy () {
    ['front', 'back'].forEach(side => {
      const url = this.form[`${side}MediaUrl`]
      const file = this.form[`${side}MediaFile`]
      if (url && file) URL.revokeObjectURL(url)
    })
  }
}
</script>

<style scoped>
.flashcard-side-label {
  font-weight: 600;
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #495057;
  margin-bottom: 6px;
}

.flashcard-side-panel {
  border-radius: 8px;
  padding: 0;
  min-height: 220px;
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
}

.flashcard-side-front {
  background: #ffffff;
  border: 2px solid #dee2e6;
}

.flashcard-side-back {
  background: #f0f4ff;
  border: 2px solid #b8c8ff;
}

.flashcard-fill {
  flex: 1;
  width: 100%;
  min-height: 220px;
  resize: none;
  border: none;
  background: transparent;
  outline: none;
  font-size: 1.6rem;
  font-weight: 600;
  text-align: center;
  padding: 70px 16px 24px;
  font-family: inherit;
  box-sizing: border-box;
  color: #212529;
  line-height: 1.4;
  border-radius: 8px;
}

.flashcard-fill::placeholder { color: transparent; }

.flashcard-fill:focus {
  outline: none;
  box-shadow: inset 0 0 0 2px #80bdff;
  border-radius: 6px;
}

.flashcard-two-col-editor {
  display: flex;
  flex-direction: row;
  align-items: stretch;
  min-height: 220px;
  flex: 1;
}

.flashcard-editor-term {
  flex: 0 0 40%;
  display: flex;
  flex-direction: column;
}

.flashcard-col-divider {
  width: 2px;
  background: #dee2e6;
  border-radius: 2px;
  align-self: stretch;
  flex-shrink: 0;
}

.flashcard-editor-media {
  flex: 0 0 60%;
  padding: 16px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.flashcard-media-only {
  padding: 16px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  flex: 1;
}

.flashcard-drop-zone {
  border: 2px dashed #ced4da;
  border-radius: 8px;
  padding: 16px;
  text-align: center;
  cursor: pointer;
  transition: border-color 0.2s, background-color 0.2s;
  background: #fafafa;
}

.flashcard-drop-zone:hover,
.drop-zone-active { border-color: #007bff; background: #f0f6ff; }

.drop-zone-has-file { border-style: solid; border-color: #28a745; background: #f6fff8; }

.drop-zone-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  color: #6c757d;
  font-size: 0.9rem;
}

.drop-zone-or { font-size: 0.8rem; color: #adb5bd; }
.drop-zone-hint { font-size: 0.75rem; color: #adb5bd; margin-top: 4px; }

.drop-zone-preview {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.preview-img {
  max-width: 100%;
  max-height: 160px;
  border-radius: 6px;
  object-fit: contain;
  margin-bottom: 8px;
}

.preview-video {
  max-width: 100%;
  max-height: 160px;
  border-radius: 6px;
  margin-bottom: 8px;
}

.flashcard-error-row { display: flex; flex-direction: row; }
.flashcard-error-term { flex: 0 0 40%; padding-right: 16px; }
.flashcard-error-divider { width: 2px; flex-shrink: 0; }
.flashcard-error-media { flex: 0 0 60%; padding-left: 16px; }
</style>

<template>
  <div class="discuss-it-attachments">
    <div v-for="(attachment, attachmentIndex) in value"
         :key="`new-attachment-${attachment.file}`"
         class="small mb-1"
    >
      <b-icon-paperclip aria-hidden="true" />
      {{ attachment.original_filename }}
      <a href=""
         class="ml-1 text-danger"
         :aria-label="`Remove ${attachment.original_filename}`"
         @click.prevent="remove(attachmentIndex)"
      >&times;</a>
    </div>
    <div v-if="canAttachMore">
      <b-form-file
        :key="fileInputKey"
        size="sm"
        class="discuss-it-attachment-input"
        :accept="acceptAttribute"
        :disabled="uploading"
        :placeholder="placeholder"
        drop-placeholder="Drop file here..."
        @input="upload"
      />
      <span v-if="uploading" class="ml-2 text-info small">
        <b-spinner small type="grow" />
        Uploading...
      </span>
    </div>
    <div class="small text-muted">
      {{ allowedTypesText }}, up to 25 MB each, {{ maxFiles }} files per comment.
    </div>
    <!-- the completion criteria count files across all of the student's comments, so say how many are still needed
         (worded like the "words needed" message for text comments) -->
    <div v-if="filesStillNeededAfterThisComment > 0" class="small text-danger">
      {{ filesStillNeededAfterThisComment }} more {{ filesStillNeededAfterThisComment === 1 ? 'file' : 'files' }} needed
      across your comments to satisfy the attachment requirement
    </div>
    <div v-if="fileErrorMessage" class="text-danger small mt-1">
      {{ fileErrorMessage }}
    </div>
  </div>
</template>

<script>
import axios from 'axios'

// keep in sync with DiscussionCommentAttachment::fileTypes(), MAX_BYTES and MAX_PER_COMMENT on the server
export const DISCUSS_IT_FILE_TYPES = {
  pdf: { label: 'PDF', extensions: ['pdf'] },
  image: { label: 'images', extensions: ['jpg', 'jpeg', 'png', 'gif', 'webp'] },
  word: { label: 'Word', extensions: ['doc', 'docx'] },
  excel: { label: 'Excel', extensions: ['xls', 'xlsx'] },
  powerpoint: { label: 'PowerPoint', extensions: ['ppt', 'pptx'] }
}
const MAX_BYTES = 25 * 1024 * 1024

/**
 * Files to attach to a Discuss-it comment.  Each file is uploaded to S3 as soon as it's chosen; v-model holds the new
 * attachments as [{ file, original_filename }], which are saved with the comment.
 */
export default {
  name: 'DiscussItFileUpload',
  props: {
    value: {
      type: Array,
      default: () => []
    },
    assignmentId: {
      type: Number,
      default: 0
    },
    allowedFileTypes: {
      type: Array,
      default: () => Object.keys(DISCUSS_IT_FILE_TYPES)
    },
    // attachments the comment already has (when editing)
    existingCount: {
      type: Number,
      default: 0
    },
    maxFiles: {
      type: Number,
      default: 5
    },
    // how many more files the completion criteria still need across all of the student's comments (0 = none)
    filesStillNeeded: {
      type: Number,
      default: 0
    }
  },
  data: () => ({
    fileInputKey: 0,
    uploading: false,
    fileErrorMessage: ''
  }),
  computed: {
    canAttachMore () {
      return this.value.length + this.existingCount < this.maxFiles
    },
    // files chosen for this comment will count once it's saved
    filesStillNeededAfterThisComment () {
      return Math.max(0, this.filesStillNeeded - this.value.length)
    },
    placeholder () {
      if (this.value.length || this.existingCount) {
        return 'Attach another file...'
      }
      return this.filesStillNeeded > 0 ? 'Attach a file...' : 'Attach a file (optional)...'
    },
    allowedKinds () {
      const kinds = this.allowedFileTypes.filter(kind => DISCUSS_IT_FILE_TYPES[kind])
      return kinds.length ? kinds : Object.keys(DISCUSS_IT_FILE_TYPES)
    },
    allowedExtensions () {
      return this.allowedKinds.reduce((extensions, kind) => extensions.concat(DISCUSS_IT_FILE_TYPES[kind].extensions), [])
    },
    acceptAttribute () {
      return this.allowedExtensions.map(extension => `.${extension}`).join(',')
    },
    allowedTypesText () {
      const labels = this.allowedKinds.map(kind => DISCUSS_IT_FILE_TYPES[kind].label)
      const text = labels.length > 1
        ? `${labels.slice(0, -1).join(', ')} or ${labels[labels.length - 1]}`
        : labels[0]
      return text.charAt(0).toUpperCase() + text.slice(1)
    }
  },
  methods: {
    remove (attachmentIndex) {
      const attachments = this.value.slice()
      attachments.splice(attachmentIndex, 1)
      this.$emit('input', attachments)
    },
    async upload (file) {
      this.fileErrorMessage = ''
      if (!file) {
        return
      }
      const extension = file.name.split('.').pop().toLowerCase()
      if (!file.name.includes('.') || !this.allowedExtensions.includes(extension)) {
        this.fileErrorMessage = `Please choose one of: ${this.allowedTypesText}.`
        this.fileInputKey++
        return
      }
      if (file.size > MAX_BYTES) {
        this.fileErrorMessage = 'That file is too large.  Files can be at most 25 MB.'
        this.fileInputKey++
        return
      }
      this.uploading = true
      this.$emit('uploading', true)
      try {
        const { data } = await axios.post('/api/s3/pre-signed-url', {
          upload_file_type: 'discuss-it-comments',
          file_name: file.name,
          assignment_id: this.assignmentId
        })
        if (data.type === 'error') {
          this.fileErrorMessage = data.message
        } else {
          const result = await fetch(data.preSignedURL, {
            method: 'PUT',
            body: file,
            headers: { 'Content-Type': file.type || 'application/octet-stream' }
          })
          if (result.ok) {
            this.$emit('input', this.value.concat([{
              file: data.discuss_it_comments_filename,
              original_filename: file.name
            }]))
          } else {
            this.fileErrorMessage = 'We were not able to upload your file.  Please try again or contact us if the problem persists.'
          }
        }
      } catch (error) {
        this.fileErrorMessage = error.message
      }
      this.uploading = false
      this.$emit('uploading', false)
      this.fileInputKey++
    }
  }
}
</script>

<style scoped>
.discuss-it-attachment-input {
  max-width: 360px;
}
</style>

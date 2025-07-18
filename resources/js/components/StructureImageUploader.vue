<template>
  <span>
    <b-button
      v-if="!processingFile && !files.length"
      variant="info"
      size="sm"
      :disabled="!submitButtonActive"
      @click="checkForSelectedFile"
    >
   Scan Molecule
    </b-button>
    <file-upload
      :key="`fileUpload-${assignmentId}`"
      ref="structureUpload"
      v-model="files"
      accept=".pdf,.txt,.png,.jpg,.jpeg"
      put-action="/put.method"
      @input-file="inputFile"
      @input-filter="inputFilter"
    />
      <span class="upload mt-3">
                <span v-if="files.length && (preSignedURL !== '')">
                  <span v-for="file in files" :key="file.id">
                    <span v-if="file.active" class="ml-2">
                      <b-spinner small type="grow"/>
                      Uploading File...
                    </span>
                    <span v-if="processingFile">
                      <b-spinner small type="grow"/>
                      Processing file...
                    </span>
                         <span v-if="file.error" class="text-danger">Error: {{ file.error }}</span>
                  </span>
              </span>
  </span>
  </span>
</template>

<script>
import { formatFileSize, inputFile, inputFilter, triggerFileSelect } from '../helpers/UploadFiles'
import Form from 'vform'
import axios from 'axios'
import { mapGetters } from 'vuex'

export default {
  name: 'StructureImageUploader',
  data: () => ({
    imageSmiles: '',
    smiles: '',
    handledOK: false,
    cancelled: false,
    processingFile: false,
    preSignedURL: '',
    clickerApp: false,
    files: [],
    uploadFileForm: new Form(),
    isStructureImageUploader: true,
    uploadFileType: 'structure'
  }),
  props: {
    submitButtonActive: {
      type: Boolean,
      default: true
    },
    assignmentId: {
      type: Number,
      default: 0
    },
    questionId: {
      type: Number,
      default: 0
    }
  },
  computed: mapGetters({
    user: 'auth/user'
  }),
  mounted () {
    this.clickerApp = window.config.clickerApp
  },
  methods:
    {
      formatFileSize,
      inputFilter,
      inputFile,
      triggerFileSelect,
      checkForSelectedFile () {
        this.triggerFileSelect()
        this.waitUntilConditionMet(() => {
          if (this.$refs.structureUpload) {
            this.$refs.structureUpload.active = true
          }
        })
      },
      waitUntilConditionMet (callback) {
        const check = () => {
          const conditionMet =
            !this.processingFile &&
            this.preSignedURL !== '' &&
            (!this.$refs.structureUpload || !this.$refs.structureUpload.active)

          if (conditionMet) {
            callback()
          } else {
            this._waitUntilTimer = setTimeout(check, 100)
          }
        }

        check() // Start checking
      },
      async handleOK () {
        if (this.uploading) {
          this.$noty.info('Please be patient while the file is uploading.')
          return false
        }
        this.processingFile = true

        try {
          let formData = {
            s3_key: this.s3Key,
            _method: 'put',
            assignment_id: this.assignmentId,
            question_id: this.questionId,
            user_id: this.user.id
          }
          const { data } = await axios.post('/api/math-pix/convert-to-smiles', formData)
          if (this.user.role === 3) {
            this.$emit('setStructureS3Key', this.s3Key)
          }
          if (data.type === 'error') {
            this.$noty.error(data.message)
          } else {
            this.smiles = data.smiles
            if (this.user.role === 3) {
              this.$emit('setImageSmiles', this.smiles)
            }
            this.importSmiles()
          }
        } catch (error) {
          this.$noty.error(error.message)
        }
        this.processingFile = false
        this.files = []
        this.$emit('updateStructureImageUploaderKey')
      },
      importSmiles () {
        const iframe = document.querySelector('iframe[src="/api/sketcher/default"]')
        iframe.contentWindow.postMessage({
          method: 'import',
          smiles: this.smiles
        }, '*')
      }
    }
}
</script>

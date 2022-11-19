<template>
  <div>
    <b-modal id="modal-framework-levels"
             title="Import Framework"
             :hide-footer="true"
             :no-close-on-backdrop="true"
             size="xl"
    >
      <b-table
        :key="`frameworklevel-${frameworkLevelKey}`"
        striped
        hover
        :no-border-collapse="true"
        :items="frameworkLevels"
        :fields="frameworkLevelFields"
      >
        <template v-slot:cell(status)="data">
          <span :class="`text-${data.item.type}`">{{ data.item.status }}</span>
        </template>
      </b-table>
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide('modal-framework-levels')"
        >
          OK
        </b-button>
      </template>
    </b-modal>
    <div class=" vld-parent">
      <loading :active.sync="isLoading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"
      />
      <PageTitle :title="frameworkTitle"/>
      <div v-if="isFrameworkOwner">
        <p>
          Frameworks are a way of organizing learning objectives, concepts, themes, or skills (descriptors). Each
          framework can be up to 4 levels deep
          with an unlimited number of descriptors associated with each framework level.
        </p>
        <p>
          You can begin by either <a href="#"
                                     @click="errorMessages= [];$refs.FrameworkAligner.initAddLevel(1,0)"
        >directly adding a Level 1 item</a> to the framework, or use the
          <a href="#"
             @click.prevent="downloadFrameworkTemplate"
          >framework template
          </a>
          to import your
          framework (descriptors are optional at the time of import).
        </p>
        <b-row>
          <b-col cols="6">
            <b-form-file
              v-model="frameworkLevelFileForm.framework_level_file"
              class="mb-2"
              placeholder="Choose a file or drop it here..."
              drop-placeholder="Drop file here..."
            />
            <div v-if="uploading">
              <b-spinner small type="grow"/>
              Uploading file...
            </div>
            <div v-for="(errorMessage, errorMessageIndex) in errorMessages" :key="`error-message-${errorMessageIndex}`">
              <ErrorMessage :message="errorMessage"/>
            </div>
          </b-col>
          <b-col>
            <b-button variant="info"
                      :disabled="disableImport"
                      @click="uploadFrameworkFile"
            >
              Import
            </b-button>
            <b-button variant="secondary"
                      @click="exportFramework"
            >
              Export
            </b-button>
          </b-col>
        </b-row>
      </div>
      <FrameworkAligner :key="`framework-${frameworkId}-${frameworkKey}`"
                        ref="FrameworkAligner"
                        :framework-id="frameworkId"
                        @setFrameworkInfo="setFrameworkInfo"
      />
    </div>
  </div>
</template>

<script>
import FrameworkAligner from '~/components/FrameworkAligner'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import { downloadFile } from '~/helpers/DownloadFiles'
import axios from 'axios'
import Form from 'vform'
import ErrorMessage from '~/components/ErrorMessage'
import { mapGetters } from 'vuex'
import { licenseOptions } from '~/helpers/Licenses'

export default {
  components: { ErrorMessage, FrameworkAligner, Loading },
  metaInfo () {
    return { title: 'Upload Framework' }
  },
  data: () => ({
    licenseOptions: licenseOptions,
    frameworkProperties: {},
    isFrameworkOwner: false,
    frameworkLevelFields: ['Level 1', 'Level 2', 'Level 3', 'Level 4','Descriptor', 'Status'],
    frameworkLevelKey: 0,
    frameworkKey: 0,
    frameworkLevels: [],
    frameworkLevelFileForm: new Form({
      framework_level_file: []
    }),
    errorMessages: [],
    uploading: false,
    disableImport: false,
    isLoading: true,
    frameworkId: 0,
    frameworkTitle: ''
  }),
  computed: {
    ...mapGetters({
      user: 'auth/user'
    })
  },
  mounted () {
    this.frameworkId = +this.$route.params.frameworkId
  },
  methods: {
    async uploadFrameworkFile () {
      this.disableImport = true
      this.errorMessages = ''
      try {
        if (this.uploading) {
          this.$noty.info('Please be patient while the file is uploading.')
          return false
        }
        this.uploading = true
        let uploadFrameworkLevelFormData = new FormData()
        uploadFrameworkLevelFormData.append('framework_level_file', this.frameworkLevelFileForm.framework_level_file)
        uploadFrameworkLevelFormData.append('_method', 'put') // add this
        uploadFrameworkLevelFormData.append('framework_id', this.frameworkId)
        const { data } = await axios.post(`/api/framework-levels/upload`, uploadFrameworkLevelFormData)
        if (data.type !== 'success') {
          this.disableImport = false
          this.errorMessages = data.message
        } else {
          this.frameworkLevelFileForm.framework_level_file = []
          this.frameworkLevels = data.framework_levels
          console.log(data)
          await this.saveFrameworkLevels()
        }
      } catch (error) {
        if (error.message.includes('status code 413')) {
          error.message = 'The maximum size allowed is 10MB.'
        }
        this.$noty.error(error.message)
      }
      this.uploading = false
      this.disableImport = false
    },
    async saveFrameworkLevels () {
      this.$bvModal.show('modal-framework-levels')
      for (let i = 0; i < this.frameworkLevels.length; i++) {
        this.frameworkLevels[i].status = 'pending'
      }
      for (let i = 0; i < this.frameworkLevels.length; i++) {
        let frameworkLevel = this.frameworkLevels[i]
        let frameworkLevelInfo = {
          title_1: frameworkLevel['Level 1'],
          title_2: frameworkLevel['Level 2'],
          title_3: frameworkLevel['Level 3'],
          title_4: frameworkLevel['Level 4'],
          descriptor: frameworkLevel.Descriptor,
          framework_id: this.frameworkId
        }
        try {
          const { data } = await axios.post('/api/framework-levels/with-descriptors', frameworkLevelInfo)
          this.frameworkLevels[i].status = data.message
          this.frameworkLevels[i].type = data.type === 'error' ? 'danger' : data.type
        } catch (error) {
          this.frameworkLevels[i].status = error.message
          this.frameworkLevels[i].type = 'danger'
        }
        this.frameworkLevelKey++
      }
      this.$noty.success('Upload complete.')
      this.frameworkKey++
    },
    exportFramework () {
      let url = `/api/frameworks/export/${this.frameworkId}`
      let license = this.licenseOptions.find(item => item.value === this.frameworkProperties.license).text
      if (this.frameworkProperties.license_version) {
        license += (this.frameworkProperties.license_version)
      }
      let filename = `${this.frameworkTitle}-${this.frameworkProperties.author}-${license}-${this.frameworkProperties.source_url}`
      filename.replace(/[/\\?%*:|"<>]/g, '-')
      downloadFile(url, [], `${filename}.csv`, this.$noty)
    },
    downloadFrameworkTemplate () {
      let url = `/api/framework-levels/template`
      downloadFile(url, [], `framework-template.csv`, this.$noty)
    },
    setFrameworkInfo (properties) {
      this.isLoading = false
      this.frameworkProperties = properties
      this.frameworkTitle = `Update ${properties.title} Framework`
      this.isFrameworkOwner = properties.user_id === this.user.id
    }
  }
}
</script>

<style scoped>

</style>

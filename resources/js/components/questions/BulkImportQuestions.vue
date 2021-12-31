<template>
  <div>
    <b-modal id="modal-my-questions-folders"
             title="My Questions Folders"
             hide-footer
    >
      <div v-if="myQuestionsFolders.length">
        <ol>
          <li v-for="myQuestionsFolder in myQuestionsFolders" :key="`my-question-folder-${myQuestionsFolder.id}`">
            {{ myQuestionsFolder.name }}
          </li>
        </ol>
      </div>
      <div v-if="!myQuestionsFolders.length">
        <b-alert variant="info" show>
          <span class="font-weight-bold">You have no My Questions folders.</span>
        </b-alert>
      </div>
      <template #modal-footer="{ ok }">
        <b-button size="sm" variant="primary"
                  @click="$bvModal.hide('modal-my-questions-folders')"
        >
          OK
        </b-button>
      </template>
    </b-modal>
    <b-container>
      <b-form-group
        label-cols-sm="3"
        label-cols-lg="2"
        label="Import Template"
      >
        <b-form-row class="pt-2">
          <b-form-radio-group
            v-model="importTemplate"
            @change="setQuestionsToImport($event)"
          >
            <b-form-radio name="import_template" value="h5p">
              H5P
            </b-form-radio>
            <b-form-radio name="import_template" value="webwork">
              WeBWorK
            </b-form-radio>
            <b-form-radio name="import_template" value="advanced">
              Advanced Template
            </b-form-radio>
          </b-form-radio-group>
        </b-form-row>
      </b-form-group>
    </b-container>
    <div v-if="importTemplate === 'h5p'">
      <b-card
        header-html="<h2 class=&quot;h7&quot;>H5P Importer</h2>"
        class="mb-4"
      >
        <b-form-group
          label-cols-sm="2"
          label-cols-lg="1"
          label-for="folder"
          label="Folder*"
        >
          <b-form-row>
            <SavedQuestionsFolders
              ref="bulkImportSavedQuestionsFolders"
              class="mt-2"
              :type="'my_questions'"
              :folder-to-choose-from="'My Questions'"
              :question-source-is-my-favorites="false"
              @savedQuestionsFolderSet="setMyCoursesFolder"
              @reloadSavedQuestionsFolders="getMyQuestionsFolders"
            />
          </b-form-row>
        </b-form-group>
        <b-card-text>
          <RequiredText :plural="false"/>
          <b-form-group
            label-for="h5p_ids"
            label="H5P IDs*"
          >
            <b-form-textarea
              id="h5p_ids"
              v-model="h5pIds"
              placeholder="1, 2, 3..."
              tabindex="0"
            />
          </b-form-group>
          <b-button variant="primary"
                    :disabled="h5pIds === ''"
                    @click="importH5PQuestions"
          >
            Import
          </b-button>
        </b-card-text>
      </b-card>
    </div>
    <div v-if="['advanced','webwork'].includes(importTemplate)">
      <b-card
        :header-html="getBulkImportHtml()"
        class="mb-4"
      >
        <SavedQuestionsFolders
          v-show="false"
          ref="bulkImportSavedQuestionsFoldersAdvanced"
          class="mt-2"
          :type="'my_questions'"
          :folder-to-choose-from="'My Questions'"
          :question-source-is-my-favorites="false"
          @reloadSavedQuestionsFolders="getMyQuestionsFolders"
        />
        <b-card-text>
          <p>Instructions:</p>
          <ol>
            <li>
              Starred fields are required.
            </li>
            <li v-if="importTemplate === 'advanced'">
              Question Type should be either assessment or exposition.
            </li>
            <li v-if="importTemplate === 'advanced'">
              Questions that are of type exposition should not have any associated technology nor should they contain
              Text Question, A11Y Question, Answer, Solution, or Hint
            </li>
            <li>
              Please enter 1 for yes and 0 for no in the Public* column.
            </li>
            <li v-if="importTemplate === 'advanced'">
              Accepted technologies are webwork, imathas, h5p. This field may be left blank.
            </li>
            <li v-if="importTemplate === 'advanced'">
              The source column may be left blank for assessment question types.
            </li>
            <li>Tags should be a comma separated list: tag 1, tag 2, tag 3.</li>
            <li>Accepted licenses are {{ validLicenses }}.</li>
            <li>
              License versions should be of the form x.y (i.e. 3.0, 4.0). If no license version is provided, Adapt will
              assume the most current license possible.
            </li>
            <li>
              Folders can be chosen from your list of <a href=""
                                                         @click.prevent="$bvModal.show('modal-my-questions-folders')"
            >My Questions folders</a> or you can <a href=""
                                                      @click.prevent="$bvModal.show('modal-add-saved-questions-folder')"
            >create a new My Questions Folder</a>.
            </li>
          </ol>
          <b-button variant="success" size="sm" @click="downloadQuestionsCSVStructure">
            Download {{ importTemplate === 'webwork' ? 'WeBWorK' : 'Advanced' }} Import File
          </b-button>
        </b-card-text>
      </b-card>
      <b-container>
        <b-row>
          <b-col cols="6">
            <b-form-file
              v-model="bulkImportQuestionsFileForm.bulkImportQuestionsFile"
              class="mb-2"
              placeholder="Choose a file or drop it here..."
              drop-placeholder="Drop file here..."
            />
            <div v-if="uploading">
              <b-spinner small type="grow"/>
              Uploading file...
            </div>
            <input type="hidden" class="form-control is-invalid">
            <div class="help-block invalid-feedback">
              {{ bulkImportQuestionsFileForm.errors.get('bulk_import_questions_file') }}
            </div>
          </b-col>
          <b-col>
            <b-button variant="info"
                      :disabled="disableImport"
                      @click="uploadBulkImportFile"
            >
              Import
            </b-button>
          </b-col>
        </b-row>
      </b-container>
      <div v-if="errorMessages.length" class="text-danger">
        Please fix the following errors:
        <ul v-for="errorMessage in errorMessages" :key="errorMessage">
          <li>{{ errorMessage }}</li>
        </ul>
      </div>
    </div>
    <div style="min-height:200px">
      <b-container v-if="questionsToImport.length" fluid>
        <b-row>
          <b-col sm="5" md="6" class="my-1">
            <b-form-group
              label="Per page"
              label-for="per-page-select"
              label-cols-sm="6"
              label-cols-md="4"
              label-cols-lg="3"
              label-align-sm="right"
              label-size="sm"
              class="mb-0"
            >
              <b-form-select
                id="per-page-select"
                v-model="perPage"
                style="width:100px"
                :options="pageOptions"
                size="sm"
              />
            </b-form-group>
          </b-col>
          <b-col sm="7" md="6" class="my-1">
            <b-pagination
              v-model="currentPage"
              :total-rows="questionsToImport.length"
              :per-page="perPage"
              align="center"
              first-number
              last-number
              class="my-0"
            />
          </b-col>
          <b-col lg="6" class="my-1">
            <b-form-group
              label="Filter"
              label-for="filter-input"
              label-cols-sm="3"
              label-align-sm="right"
              label-size="sm"
              class="mb-0"
            >
              <b-input-group size="sm">
                <b-form-input
                  id="filter-input"
                  v-model="filter"
                  type="search"
                  placeholder="Type to Search"
                />

                <b-input-group-append>
                  <b-button :disabled="!filter" @click="filter = ''">
                    Clear
                  </b-button>
                </b-input-group-append>
              </b-input-group>
            </b-form-group>
          </b-col>
        </b-row>
        <b-table
          aria-label="QuestionsToImport"
          striped
          hover
          responsive
          :no-border-collapse="true"
          :fields="fields"
          :per-page="perPage"
          :current-page="currentPage"
          :filter="filter"
          :items="questionsToImport"
        >
          <template v-slot:cell(import_status)="data">
            <span v-html="data.item.import_status"/>
          </template>
          <template v-slot:cell(title)="data">
            <span v-if="data.item.url">
              <a :href="data.item.url" target="_blank">{{ data.item.title }}</a></span>
            <span v-if="!data.item.url">{{ data.item.title }}</span>
          </template>
          <template v-slot:cell(Title*)="data">
            <span v-if="data.item.url">
              <a :href="data.item.url" target="_blank">{{ data.item['Title*'] }}</a></span>
            <span v-if="!data.item.url">{{ data.item['Title*'] }}</span>
          </template>
          <template v-if="importTemplate === 'advanced'" v-slot:cell(Technology)="data">
            {{ data.item.Technology === '' ? 'text' : data.item.Technology }}
          </template>
        </b-table>
      </b-container>
    </div>
  </div>
</template>

<script>

import { downloadFile } from '~/helpers/DownloadFiles'
import axios from 'axios'
import Form from 'vform'
import SavedQuestionsFolders from '~/components/SavedQuestionsFolders'

let h5pFields = [
  {
    key: 'id',
    label: 'ID'
  },
  'title',
  {
    key: 'tags',
    formatter: value => {
      if (value === 'N/A') {
        return value
      }
      return value.length
        ? value.join(', ')
        : 'none'
    }
  },
  'import_status'
]
export default {
  name: 'BulkImportQuestions',
  components: { SavedQuestionsFolders },
  data: () => ({
    myQuestionsFolders: [],
    folderId: 0,
    disableImport: false,
    filter: null,
    questionsToImportValidationErrors: [],
    fields: h5pFields,
    h5pImports: [],
    h5pIds: '',
    importTemplate: 'h5p',
    currentPage: 1,
    pageOptions: [10, 50, 100, 500, { value: 10000, text: 'Show All' }],
    perPage: 10,
    questionsToImport: [],
    errorMessages: [],
    validLicenses: '',
    bulkImportStopped: false,
    uploading: false,
    bulkImportQuestionsFileForm: new Form({
      bulkImportQuestionsFile: []
    })
  }),
  mounted () {
    this.getMyQuestionsFolders()
    this.getValidLicenses()
  },
  methods: {
    async getMyQuestionsFolders (type = 'my_questions') {
      try {
        const { data } = await axios.get(`/api/saved-questions-folders/${type}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.myQuestionsFolders = data.saved_questions_folders
      } catch (error) {
        this.$noty.error(error.message)
      }
      return this.savedQuestionsFoldersOptions
    },
    setMyCoursesFolder (myCoursesFolder) {
      this.folderId = myCoursesFolder
    },
    getBulkImportHtml () {
      let type = this.importTemplate === 'webwork' ? 'WeBWorK' : 'Advanced'
      return `<h2 class="h7">Download ${type} Import File</h2>`
    },
    setQuestionsToImport (type) {
      this.questionsToImport = []
      switch (type) {
        case ('h5p'):
          this.fields = h5pFields
          break
        case ('webwork'):
          this.fields = [
            {
              key: 'row',
              label: 'CSV Row',
              isRowHeader: true
            },
            {
              key: 'Title*',
              label: 'Title'
            },
            {
              key: 'File Path*',
              label: 'File Path'
            },
            {
              key: 'Tags',
              formatter: value => {
                return value.length
                  ? value.join(', ')
                  : 'none'
              }
            },
            'import_status'
          ]
          break
        case ('advanced'):
          this.fields = [
            {
              key: 'row',
              isRowHeader: true
            },
            {
              key: 'Title*',
              label: 'Title'
            },
            'Technology',
            {
              key: 'Tags',
              formatter: value => {
                return value.length
                  ? value.join(', ')
                  : 'none'
              }
            },
            'import_status'
          ]
          break
        default:
          alert('not valid type')
      }
    },
    async importH5PQuestions () {
      if (!this.folderId) {
        this.$noty.info('Please choose a My Questions folder.')
        return false
      }
      let h5pIds = this.h5pIds.split(',')
      this.questionsToImport = []
      for (let i = 0; i < h5pIds.length; i++) {
        this.questionsToImport.splice(i, 0, {
          id: h5pIds[i],
          import_status: 'Pending',
          title: 'N/A',
          author: 'N/A',
          tags: 'N/A'
        })
      }
      for (let i = 0; i < h5pIds.length; i++) {
        let h5pId = this.questionsToImport[i].id
        let questionToImport = {
          id: h5pIds[i],
          import_status: 'Pending',
          title: 'N/A',
          author: 'N/A',
          tags: 'N/A'
        }
        try {
          const { data } = await axios.post(`/api/questions/h5p/${h5pId}`, { folder_id: this.folderId })
          if (data.type === 'success') {
            questionToImport =
              {
                id: h5pId,
                tags: data.h5p.tags,
                title: data.h5p.title,
                url: data.h5p.url,
                author: data.h5p.author,
                import_status: '<span class="text-success">Success</span>'
              }
          } else {
            questionToImport.import_status = `<span class="text-danger">Error: ${data.message}</span>`
          }
        } catch (error) {
          questionToImport.import_status = `<span class="text-danger">Error: ${error.message}</span>`
        }
        this.questionsToImport.splice(i, 1, questionToImport)
      }
      this.h5pIds = ''
    },
    async getValidLicenses () {
      try {
        const { data } = await axios.get('/api/questions/valid-licenses')
        this.validLicenses = data.licenses.join(', ')
      } catch (error) {
        this.$noty.error('We were not able to retrieve the list of valid licenses.')
      }
    },
    async downloadQuestionsCSVStructure () {
      downloadFile(`/api/questions/bulk-upload-template/${this.importTemplate}`, [], `${this.importTemplate}-import-template.csv`, this.$noty)
    },
    stopBulkImport () {
      this.bulkImportStopped = true
    },
    async uploadBulkImportFile () {
      this.disableImport = true
      this.errorMessages = []
      try {
        if (this.uploading) {
          this.$noty.info('Please be patient while the file is uploading.')
          return false
        }

        this.uploading = true
        let formData = new FormData()
        formData.append('bulk_import_questions_file', this.bulkImportQuestionsFileForm.bulkImportQuestionsFile)
        formData.append('_method', 'put') // add this
        formData.append('import_template', this.importTemplate)
        const { data } = await axios.post(`/api/questions/validate-bulk-import-questions`, formData)
        if (data.type === 'success') {
          await this.importQuestions(data.questions_to_import)
        } else {
          this.disableImport = false
          this.errorMessages = data.message
        }
      } catch (error) {
        if (error.message.includes('status code 413')) {
          error.message = 'The maximum size allowed is 10MB.'
        }
        this.$noty.error(error.message)
      }
      this.uploading = false
      this.disableImport = false
      this.bulkImportQuestionsFileForm.bulkImportQuestionsFile = []
    },
    async importQuestions (questionsToImport) {
      console.log(questionsToImport)
      this.questionsToImport = questionsToImport
      let questionForm
      for (let i = 0; i < this.questionsToImport.length; i++) {
        let question = this.questionsToImport[i]
        if (this.importTemplate === 'webwork') {
          question['Question Type*'] = 'assessment'
          question['Auto-Graded Technology'] = 'webwork'
          question['Technology ID/File Path'] = question['File Path*']
          question['Non-Technology Text'] = null
          question['A11Y Question'] = null
          question['Answer'] = null
          question['Solution'] = null
          question['Hint'] = null
        }
        try {
          questionForm = new Form({
            folder_id: question['folder_id'],
            question_type: question['Question Type*'],
            public: question['Public*'],
            title: question['Title*'],
            non_technology_text: question['Source'],
            technology: question['Auto-Graded Technology'],
            technology_id: question['Technology ID/File Path'],
            text_question: question['Text Question'],
            a11y_question: question['A11Y Question'],
            answer_html: question['Answer'],
            solution_html: question['Solution'],
            hint: question['Hint'],
            author: question['Author'],
            tags: question['Tags'],
            license: question['License'],
            license_version: question['License Version']
          })

          const { data } = await questionForm.post('/api/questions')
          if (data.type === 'success') {
            question.import_status = '<span class="text-success">Success</span>'
            question.url = data.url
          } else {
            question.import_status = '<span class="text-danger">Error</span>'
          }
        } catch (error) {
          if (error.message.includes('status code 422')) {
            let errors = questionForm.errors.flatten()
            let addS = errors.length > 1 ? '(s)' : ''
            question.import_status = `<div class="text-danger">Error${addS}</div><ul>`
            for (let i = 0; i < errors.length; i++) {
              question.import_status += `<li>${errors[i]}</li>`
            }
            question.import_status += '</ul>'
          } else {
            question.import_status = `<div class="text-danger">Error: ${error.message}</div>`
          }
        }
      }
    }
  }
}
</script>

<style scoped>

</style>

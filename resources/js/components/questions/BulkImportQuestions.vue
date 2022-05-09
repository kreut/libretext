<template>
  <div>
    <AllFormErrors :all-form-errors="allFormErrors" :modal-id="'modal-form-errors-file-upload'"/>
    <b-modal id="modal-my-assignments-and-topics"
             title="Assignments and Topics"
             hide-footer
    >
      <b-form-select
        id="per-page-select"
        v-model="course"
        :options="myCoursesOptions"
        @change="getAssignmentsAndTopicsByCourse($event)"
      />
      <ol class="mt-2">
        <li v-for="assignment in assignments" :key="`assignment-${assignment.id}`">
          <span :id="`copy-assignment-${assignment.id}`">{{ assignment.name }}</span> <a
          href="#"
          class="pr-1"
          aria-label="Copy Assignment"
          @click.prevent="doCopy(`copy-assignment-${assignment.id}`)"
        >
          <font-awesome-icon
            :icon="copyIcon"
          />
        </a>
          <ul v-if="assignment.topics.length">
            <li v-for="topic in assignment.topics" :key="`topic-${topic.id}`">
              <span :id="`copy-topic-${topic.id}`">{{ topic.name }}</span> <a
              href="#"
              class="pr-1"
              aria-label="Copy Topic"
              @click.prevent="doCopy(`copy-topic-${topic.id}`)"
            >
              <font-awesome-icon
                :icon="copyIcon"
              />
            </a>
            </li>
          </ul>
        </li>
      </ol>
    </b-modal>
    <b-modal id="modal-my-questions-folders"
             title="My Questions Folders"
             hide-footer
    >
      <div v-if="myQuestionsFolders.length">
        <ol>
          <li v-for="myQuestionsFolder in myQuestionsFolders" :key="`my-question-folder-${myQuestionsFolder.text}`">
            {{ myQuestionsFolder.text }}
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
      <p>
        Instead of creating questions one at a time, you can bulk import them into ADAPT. Using the H5P importer, you
        can import a comma separated
        list of H5P questions directly into My Questions. Using the WebWork importer, you can import questions into My
        Questions as well as directly
        into a course. And, the Advanced Template provides the most comprehensive set of options, mirroring the New
        Question interface.
      </p>
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
            <b-form-radio name="import_template" value="qti">
              QTI
            </b-form-radio>
          </b-form-radio-group>
        </b-form-row>
      </b-form-group>
    </b-container>
    <div>
      <b-card
        v-if="importTemplate === 'h5p'"
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
              :key="`bulk-import-saved-questions-folder-${bulkImportSavedQuestionsKey}`"
              class="mt-2"
              :modal-id="'modal-for-bulk-import'"
              :type="'my_questions'"
              :folder-to-choose-from="'My Questions'"
              :question-source-is-my-favorites="false"
              :create-modal-add-saved-questions-folder="true"
              @savedQuestionsFolderSet="setMyCoursesFolder"
              @exportSavedQuestionsFolders="exportSavedQuestionsFolders"
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
    <div v-if="['advanced','webwork','qti'].includes(importTemplate)">
      <b-card
        :header-html="getBulkImportHtml()"
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
              :key="`qti-saved-questions-folder-${bulkImportSavedQuestionsKey}`"
              class="mt-2"
              :modal-id="'modal-for-qti-import'"
              :type="'my_questions'"
              :folder-to-choose-from="'My Questions'"
              :question-source-is-my-favorites="false"
              :create-modal-add-saved-questions-folder="true"
              @savedQuestionsFolderSet="setMyCoursesFolder"
              @exportSavedQuestionsFolders="exportSavedQuestionsFolders"
            />
          </b-form-row>
        </b-form-group>
        <SavedQuestionsFolders
          v-if="['advanced','h5p'].includes(importTemplate)"
          v-show="false"
          ref="bulkImportSavedQuestionsFoldersAdvanced"
          class="mt-2"
          :type="'my_questions'"
          :folder-to-choose-from="'My Questions'"
          :question-source-is-my-favorites="false"
        />
        <b-card-text>
          <b-button v-if="['advanced','webwork'].includes(importTemplate)"
                    variant="secondary"
                    size="sm"
                    @click="$bvModal.show('modal-bulk-upload-instructions')"
          >
            Instructions
          </b-button>
          <b-modal id="modal-bulk-upload-instructions"
                   title="Bulk Upload Instructions"
                   size="lg"
                   hide-footer
          >
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
                Accepted technologies are webwork, imathas, h5p. This field may be left blank for text-only questions.
              </li>
              <li v-if="importTemplate === 'advanced'">
                The source column may be left blank for assessment question types assuming that you are using one of the
                auto-graded technologies.
              </li>
              <li>Tags should be a comma separated list: tag 1, tag 2, tag 3.</li>
              <li>Accepted licenses are {{ validLicenses }}.</li>
              <li>
                License versions should be of the form x.y (i.e. 3.0, 4.0). If no license version is provided, Adapt
                will
                assume the most current license possible.
              </li>
              <li>
                Folders can be chosen from your list of <a href=""
                                                           @click.prevent="$bvModal.show('modal-my-questions-folders')"
              >My Questions folders</a> or you can create a new My Questions Folder while you import your questions.
              </li>
              <li>
                To upload your questions directly into an assignment, the assignment will need to first be created in
                the
                course or you will need to create an <a href="/instructors/assignment-templates" target="_blank">assignment
                template</a> and specify which template. Within these assignments
                you can further <a href=""
                                   @click.prevent="$bvModal.show('modal-my-assignments-and-topics')"
              > categorize by topic</a> or create new topics as you import your questions.
              </li>
            </ol>
          </b-modal>
          <b-form-group
            v-if="['advanced','webwork'].includes(importTemplate)"
            label-cols-sm="3"
            label-cols-lg="2"
            label="Import questions to:"
            label-for="import_questions_to"
          >
            <b-form-select
              id="import_questions_to"
              v-model="importToCourse"
              style="width:400px"
              :options="importToCourseOptions"
            />
          </b-form-group>
          <b-button
            v-if="['advanced','webwork'].includes(importTemplate)"
            variant="success"
            size="sm"
            @click="downloadQuestionsCSVStructure"
          >
            Download {{ importTemplate === 'webwork' ? 'WeBWorK' : 'Advanced' }} Import Template
          </b-button>
          <b-container>
            <b-row v-show="importTemplate === 'qti'">
              <file-upload
                ref="upload"
                v-model="files"
                accept=".zip"
                put-action="/put.method"
                @input-file="inputFile"
                @input-filter="inputFilter"
              />
            </b-row>
            <div class="upload mt-3">
              <ul v-if="files.length && (preSignedURL !== '')">
                <li v-for="file in files" :key="file.id">
                    <span :class="file.success ? 'text-success font-weight-bold' : ''">{{
                        file.name
                      }}</span> -
                  <span>{{ formatFileSize(file.size) }} </span>
                  <span v-if="file.size > 10000000">Note: large files may take up to a minute to process.</span>
                  <span v-if="file.error" class="text-danger">Error: {{ file.error }}</span>
                  <span v-else-if="file.active" class="ml-2">
                      <b-spinner small type="grow"/>
                      Uploading File...
                    </span>
                  <span v-if="processingFile">
                      <b-spinner small type="grow"/>
                      Processing file...
                    </span>
                  <b-button v-if="!processingFile && (preSignedURL !== '') && (!$refs.upload || !$refs.upload.active)"
                            variant="success"
                            size="sm"
                            style="vertical-align: top"
                            :disabled="disableQtiStartUpload"
                            @click.prevent="$refs.upload.active = true"
                  >
                    Start Upload
                  </b-button>
                </li>
              </ul>
            </div>
            <b-row v-if="['advanced','webwork'].includes(importTemplate)">
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
        </b-card-text>
      </b-card>

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
        {{ questionsToImport }}
        <b-table v-if="importTemplate === 'qti'"
                 aria-label="Qti questions to import"
                 striped
                 hover
                 responsive
                 :no-border-collapse="true"
                 :per-page="perPage"
                 :current-page="currentPage"
                 :filter="filter"
                 :items="questionsToImport"
        >
          <template v-slot:cell(import_status)="data">
            <span v-html="data.item.import_status"/>
          </template>
        </b-table>
        <b-table
          v-if="['advanced','webwork','h5p'].includes(importTemplate)"
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
import { doCopy } from '~/helpers/Copy'
import { downloadFile } from '~/helpers/DownloadFiles'
import axios from 'axios'
import Form from 'vform'
import SavedQuestionsFolders from '~/components/SavedQuestionsFolders'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faCopy } from '@fortawesome/free-regular-svg-icons'
import { fixInvalid } from '~/helpers/accessibility/FixInvalid'
import AllFormErrors from '~/components/AllFormErrors'
import Vue from 'vue'
import { makeFileUploaderAccessible } from '~/helpers/accessibility/makeFileUploaderAccessible'
import { formatFileSize } from '~/helpers/UploadFiles'

const VueUploadComponent = require('vue-upload-component')
Vue.component('file-upload', VueUploadComponent)

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
  components: {
    SavedQuestionsFolders,
    FontAwesomeIcon,
    AllFormErrors,
    FileUpload: VueUploadComponent
  },
  data: () => ({
    disableQtiStartUpload: false,
    qtiFile: '',
    processingFile: false,
    s3Key: '',
    originalFilename: '',
    handledOK: false,
    preSignedURL: '',
    allFormErrors: [],
    files: [],
    importToCourseOptions: [],
    importToCourse: 0,
    copyIcon: faCopy,
    assignments: {},
    course: null,
    myCoursesOptions: [],
    bulkImportSavedQuestionsKey: 0,
    myQuestionsFolders: [],
    folderId: 0,
    disableImport: false,
    filter: null,
    questionsToImportValidationErrors: [],
    fields: h5pFields,
    h5pImports: [],
    h5pIds: '',
    importTemplate: 'qti',
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
    }),
    uploadFileForm: new Form({
      type: 'qti'
    })
  }),
  mounted () {
    this.doCopy = doCopy
    this.formatFileSize = formatFileSize
    this.bulkImportSavedQuestionsKey++
    this.getValidLicenses()
    this.getMyCourses()
    this.$nextTick(() => {
      makeFileUploaderAccessible()
    })
  },
  methods: {
    async inputFilter (newFile, oldFile, prevent) {
      this.uploadFileForm.errors.clear()
      this.errorMessages = []
      if (newFile && !oldFile) {
        // Filter non-image file
        if (parseInt(newFile.size) > 10000000) {
          this.$noty.info('This is big')
        }
        if (parseInt(newFile.size) > 30000000) {
          let message = '30 MB max allowed.  Your file is too large.  '
          this.uploadFileForm.errors.set('qti', message)
          this.$nextTick(() => fixInvalid())
          this.allFormErrors = this.uploadFileForm.errors.flatten()
          this.$bvModal.show('modal-form-errors-file-upload')

          return prevent()
        }

        let validExtension
        validExtension = /\.(zip)$/i.test(newFile.name)
        if (!validExtension) {
          this.uploadFileForm.errors.set('qti', 'Zip files are the only ones accepted')
          this.$nextTick(() => fixInvalid())
          this.allFormErrors = this.uploadFileForm.errors.flatten()
          this.$bvModal.show('modal-form-errors-file-upload')
          return prevent()
        } else {
          try {
            this.preSignedURL = ''
            let uploadFileData = {
              upload_file_type: 'qti',
              file_name: newFile.name
            }
            const { data } = await axios.post('/api/s3/pre-signed-url', uploadFileData)
            if (data.type === 'error') {
              this.$noty.error(data.message)
              return false
            }
            this.preSignedURL = data.preSignedURL
            newFile.putAction = this.preSignedURL

            this.s3Key = data.s3_key
            this.originalFilename = newFile.name
            this.qtiFile = data.qti_file
            this.handledOK = false
          } catch (error) {
            this.$noty.error(error.message)
            return false
          }
        }
      }

      // Create a blob field
      newFile.blob = ''
      let URL = window.URL || window.webkitURL
      if (URL && URL.createObjectURL) {
        newFile.blob = URL.createObjectURL(newFile.file)
      }
      console.log(newFile.blob)
    },
    inputFile (newFile, oldFile) {
      if (newFile && oldFile && !newFile.active && oldFile.active) {
        // Get response data
        if (newFile.xhr) {
          //  Get the response status code
          console.log('status', newFile.xhr.status)
          if (newFile.xhr.status === 200) {
            if (!this.handledOK) {
              this.handledOK = true
              console.log(this.handledOK)
              this.disableQtiStartUpload = true
              this.handleOK()
            }
          } else {
            this.$noty.error('We were not able to save your file to our server.  Please try again or contact us if the problem persists.')
          }
        } else {
          this.$noty.error('We were not able to save your file to our server.  Please try again or contact us if the problem persists.')
        }
      }
    },
    async validateQtiImport (qtiFile) {
      const { data } = await axios.post(`/api/questions/validate-bulk-import-questions`,
        { import_template: 'qti', qti_file: qtiFile, _method: 'put' })
      return data
    },
    async importQtiQuestions (directory) {
      for (let i = 0; i < this.questionsToImport.length; i++) {
        let questionToImport = this.questionsToImport[i]
        try {
          const { data } = await axios.post(`/api/qti-import`,
            {
              directory: directory,
              filename: questionToImport.filename
            })
          questionToImport.import_status = data.type === 'success'
            ? '<span class="text-success">Success</span>'
            : `<span class="text-danger">Error: ${data.message}</span>`
        } catch (error) {
          questionToImport.import_status = `<span class="text-danger">Error: ${error.message}</span>`
        }
      }
      this.files = []
    },
    async handleOK () {
      this.uploadFileForm.errors.clear('qti')
      this.processingFile = true
      try {
        let validated = await this.validateQtiImport(this.qtiFile)
        if (validated.type !== 'success') {
          this.errorMessages = validated.message
          this.processingFile = false
          this.files = []
          this.disableQtiStartUpload = false
          return false
        }
        this.questionsToImport = validated.questions_to_import
        await this.importQtiQuestions(validated.directory)
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.processingFile = false
      this.disableQtiStartUpload = false
    },
    async getAssignmentsAndTopicsByCourse (course) {
      try {
        const { data } = await axios.get(`/api/assignments/courses/${course}`)
        console.log(data)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.assignments = data.assignments
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async getMyCourses () {
      try {
        this.importToCourseOptions = [{ value: 0, text: `No specific course; import only to My Questions` }]
        this.myCoursesOptions = [{ value: null, text: `Please choose a course` }]
        const { data } = await axios.get('/api/courses')
        console.log(data)
        if (data.type !== 'success') {
          this.$noty.error(data.message)
          return false
        }
        if (data.courses) {
          for (let i = 0; i < data.courses.length; i++) {
            let course = data.courses[i]
            this.myCoursesOptions.push({ value: course.id, text: course.name })
            this.importToCourseOptions.push({ value: course.id, text: course.name })
          }
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    exportSavedQuestionsFolders (savedQuestionsFolders) {
      this.myQuestionsFolders = savedQuestionsFolders.filter(folder => folder.value)
    },
    setMyCoursesFolder (myCoursesFolder) {
      this.folderId = myCoursesFolder
    },
    getBulkImportHtml () {
      if (this.importTemplate === 'qti') {
        return `<h2 class="h7">QTI Importer</h2>`
      } else {
        let type = this.importTemplate === 'webwork' ? 'WeBWorK' : 'Advanced'
        return `<h2 class="h7">Download ${type} Import Template</h2>`
      }
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
        case ('qti'):
          this.fields = [{
            key: 'file',
            isRowHeader: true
          },
            'import_status']
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
      let url = `/api/questions/bulk-upload-template/${this.importTemplate}`
      if (this.importToCourse) {
        url += `/${this.importToCourse}`
      }
      downloadFile(url, [], `${this.importTemplate}-import-template.csv`, this.$noty)
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
        formData.append('course_id', this.importToCourse)
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
        console.log(question)
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
            course_id: this.importToCourse,
            assignment: question['Assignment'],
            topic: question['Topic'],
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
            license_version: question['License Version'],
            bulk_upload_into_assignment: true
          })
          const { data } = await questionForm.post('/api/questions')
          if (data.type === 'success') {
            question.import_status = '<span class="text-success">Success</span>'
            question.url = data.url
          } else {
            question.import_status = `<span class="text-danger">Error: ${data.message}</span>`
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
      this.$noty.info('Upload complete.')
    }
  }
}
</script>

<style scoped>

</style>

<template>
  <div v-if="assignmentFiles.length>0">
    <div class="overflow-auto">
      <b-pagination
        v-on:input="changePage"
        v-model="currentPage"
        :total-rows="assignmentFiles.length"
        :per-page="perPage"
        align="center"
        first-number
        last-number
      ></b-pagination>
    </div>

    <div class="container">
      <div class="row">
        <div class="col-sm">
          <b-card title="Submission Information">
            <b-card-text>
              <p>
                Name: {{ this.assignmentFiles[currentPage - 1]['name'] }}<br>
                Date Submitted: {{ this.assignmentFiles[currentPage - 1]['date_submitted'] }}<br>
                Date Graded: {{ this.assignmentFiles[currentPage - 1]['date_graded'] }}<br>
                Score: {{ this.assignmentFiles[currentPage - 1]['score'] }}</p>
            </b-card-text>
          </b-card>
          <b-card class="mt-2" title="Score for this submission" sub-title="TODO: Extra information how this is scored">
            <b-card-text>
              <b-form ref="scoreForm">
                <b-input-group>
                  <b-form-input
                    id="name"
                    v-model="scoreForm.score"
                    type="text"
                    placeholder="Enter the score"
                    :class="{ 'is-invalid': scoreForm.errors.has('score') }"
                    @keydown="scoreForm.errors.clear('score')"
                  >
                  </b-form-input>
                  <has-error :form="scoreForm" field="score"></has-error>
                  <b-button class="ml-3" variant="primary" v-on:click="submitScoreForm">Submit Score</b-button>
                </b-input-group>
              </b-form>
            </b-card-text>
          </b-card>
        </div>

        <div class="col-sm">
          <b-card title="Optional Text Comments">
            <b-card-text>
              <b-form ref="form">
                <b-form-textarea
                  id="text_comments"
                  v-model="textFeedbackForm.textFeedback"
                  placeholder="Enter something..."
                  rows="6"
                  max-rows="6"
                  :class="{ 'is-invalid': textFeedbackForm.errors.has('textFeedback') }"
                  @keydown="textFeedbackForm.errors.clear('textFeedback')"
                >

                </b-form-textarea>
                <has-error :form="textFeedbackForm" field="textFeedback"></has-error>
                <div class="row mt-4 float-right">
                  <b-button variant="primary" v-on:click="submitTextFeedbackForm">Save Comments</b-button>
                </div>
              </b-form>
            </b-card-text>
          </b-card>
        </div>
      </div>
    </div>
    <hr>
    <div class="container">
      <div class="row">
        <div class="col-sm">
          <b-button v-on:click="downloadAssignmentFile(currentPage)">Download Submission</b-button>
        </div>
        <b-form ref="form">
          <div class="col-sm">
            <b-input-group>
              <b-form-file
                ref="fileFeedbackInput"
                v-model="fileFeedbackForm.fileFeedback"
                placeholder="Choose a .pdf file..."
                drop-placeholder="Drop file here..."
                accept=".pdf"
              ></b-form-file>
              <span class="ml-3">
                <b-button variant="primary" v-on:click="uploadFileFeedback()">Upload Feedback</b-button>
                </span>
            </b-input-group>
            <div v-if="uploading">
              <b-spinner small type="grow"></b-spinner>
              Uploading file...
            </div>
            <input type="hidden" class="form-control is-invalid">
            <div class="help-block invalid-feedback">{{ fileFeedbackForm.errors.get('fileFeedback')}}
            </div>


          </div>

        </b-form>
      </div>
    </div>

    <iframe v-if="assignmentFiles.length>0" :src="assignmentFiles[currentPage - 1]['submission_url']"></iframe>
    <iframe v-if="assignmentFiles.length>0" :src="assignmentFiles[currentPage - 1]['file_feedback_url']"></iframe>
  </div>
</template>

<script>
  import axios from 'axios'
  import Form from "vform"
  //import pdf from 'vue-pdf'


  export default {
    /*components: {
      //pdf
    },*/
    middleware: 'auth',
    data: () => ({
      uploading: false,
      currentPage: 1,
      perPage: 1,
      assignmentFiles: [],
      textFeedbackForm: new Form({
        textFeedback: ''
      }),
      fileFeedbackForm: new Form({
        fileFeedback: null
      }),
      scoreForm: new Form({
        score: ''
      }),
    }),
    mounted() {
      this.assignmentId = this.$route.params.assignmentId
      this.getAssignmentFiles(this.assignmentId)
    },
    methods: {
      submitScoreForm(){
        alert("todo")
      },
      async uploadFileFeedback() {
        try {
          this.fileFeedbackForm.errors.set('fileFeedback', null)
          this.uploading = true
          //https://stackoverflow.com/questions/49328956/file-upload-with-vue-and-laravel
          let formData = new FormData();
          formData.append('fileFeedback', this.fileFeedbackForm.fileFeedback)
          formData.append('assignmentId', this.assignmentId)
          formData.append('userId', this.assignmentFiles[this.currentPage - 1]['user_id'])
          formData.append('_method', 'put') // add this
          const {data} = await axios.post('/api/assignment-files/file-feedback', formData)
          if (data.type === 'error') {
            this.fileFeedbackForm.errors.set('fileFeedback', data.message)
          } else {
            this.$noty.success(data.message)
            this.assignmentFiles[this.currentPage - 1]['file_feedback_url'] = data.file_feedback_url
          }
        } catch (error) {
          if (error.message.includes('status code 413')) {
            error.message = 'The maximum size allowed is 10MB.'
          }
          this.$noty.error(error.message)

        }
        this.uploading = false
        this.$refs['fileFeedbackInput'].reset()

      },
      async submitTextFeedbackForm() {
        try {

          this.textFeedbackForm.assignmentId = this.assignmentId
          this.textFeedbackForm.userId = this.assignmentFiles[this.currentPage - 1]['user_id']

          const {data} = await this.textFeedbackForm.post('/api/assignment-files/text-feedback')
          this.$noty[data.type](data.message)
          if (data.type === 'success') {
            this.assignmentFiles[this.currentPage - 1]['text_feedback'] = this.textFeedbackForm.textFeedback
          }
        } catch (error) {
          if (!error.message.includes('status code 422')) {
            this.$noty.error(error.message)
          }
        }

      },
      async downloadAssignmentFile(currentPage) {
        try {
          const {data} = await axios({
            method: 'post',
            url: '/api/assignment-files/download',
            responseType: 'arraybuffer',
            data: {
              'assignment_id': this.assignmentId,
              'submission': this.assignmentFiles[this.currentPage - 1]['submission']
            }
          })
          this.$noty.success("The assignment file is being downloaded")
          let blob = new Blob([data], {type: 'application/pdf'})
          let link = document.createElement('a')
          link.href = window.URL.createObjectURL(blob)
          link.download = this.assignmentFiles[this.currentPage - 1]['original_filename']
          link.click()
        } catch (error) {
          this.$noty.error(error.message)
        }

      },
      async changePage(currentPage) {
        this.textFeedbackForm.textFeedback = this.assignmentFiles[this.currentPage - 1]['text_feedback']
        console.log(this.assignmentFiles[currentPage - 1])
        if (this.assignmentFiles[currentPage - 1]['submission']) {
          const {data} = await axios.post('/api/assignment-files/get-temporary-url-from-request',
            {
              'assignment_id': this.assignmentId,
              'file': this.assignmentFiles[currentPage - 1]['submission']
            })
          this.assignmentFiles[currentPage - 1]['submission_url'] = data
        }
        if (this.assignmentFiles[currentPage - 1]['file_feedback']) {

          const {data} = await axios.post('/api/assignment-files/get-temporary-url-from-request',
            {
              'assignment_id': this.assignmentId,
              'file': this.assignmentFiles[currentPage - 1]['file_feedback']
            })
          this.assignmentFiles[currentPage - 1]['file_feedback_url'] = data
        }
      },
      assignmentUrlExists(currentPage) {
        return (this.assignmentFiles[currentPage - 1]['submission_url'] !== null)
      },
      async getAssignmentFiles() {
        const {data} = await axios.get(`/api/assignment-files/${this.assignmentId}`)
        this.assignmentFiles = data
        this.textFeedbackForm.textFeedback = this.assignmentFiles[0]['text_feedback']
      }
    }
  }
</script>

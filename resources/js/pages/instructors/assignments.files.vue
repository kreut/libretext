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
        </div>

        <div class="col-sm">
          <b-card title="Optional Text Comments">
            <b-card-text>
              <b-form ref="form">
                <b-form-textarea
                  id="text_comments"
                  v-model="textFeedbackForm.textFeedback"
                  placeholder="Enter something..."
                  rows="4"
                  max-rows="4"
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
            <b-form-file
              ref="feedbackFile"
              v-model="fileFeedbackForm.fileFeedback"
              placeholder="Choose a .pdf file..."
              drop-placeholder="Drop file here..."
              accept=".pdf"
            ></b-form-file>
            <div v-if="uploading">
              <b-spinner small type="grow"></b-spinner>
              Uploading file...
            </div>
            <input type="hidden" class="form-control is-invalid">
            <div class="help-block invalid-feedback">{{ fileFeedbackForm.errors.get('fileFeedback')}}
            </div>
            <b-button variant="primary" v-on:click="uploadFileFeedback()">Upload Feedback</b-button>
          </div>
        </b-form>
      </div>
    </div>

    <iframe v-if="assignmentFiles.length>0" :src="getAssignmentUrl(currentPage)"></iframe>
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
      fileFeedbackForm : new Form({
          fileFeedback: null
      })
    }),
    created() {
      this.assignmentId = this.$route.params.assignmentId
      this.assignmentFiles = this.getAssignmentFiles(this.assignmentId)
    },
    methods: {
      async uploadFileFeedback(){
        try {
          this.fileFeedbackForm.errors.set('fileFeedback', null)
          this.uploading = true
          //https://stackoverflow.com/questions/49328956/file-upload-with-vue-and-laravel
          let formData = new FormData();
          formData.append('fileFeedback', this.fileFeedbackForm.fileFeedback)
          formData.append('assignmentId', this.assignmentId)
          formData.append('userId', this.assignmentFiles[this.currentPage-1]['user_id'])
          formData.append('_method', 'put') // add this
          const {data} = await axios.post('/api/uploads/feedback-file', formData)
          if (data.type === 'error') {
            this.fileFeedbackForm.errors.set('fileFeedback', data.message)
          } else {
            this.$noty.success(data.message)

          }
        } catch (error) {
          if (error.message.includes('status code 413')){
            error.message = 'The maximum size allowed is 10MB.'
          }
          this.$noty.error(error.message)

        }
        this.uploading = false
        this.$refs['assignmentFileInput'].reset()

      },
      submitTextFeedbackForm() {
       console.log(this.textFeedbackForm)
      },
      async downloadAssignmentFile(currentPage) {
        try {
          const {data} = await axios({
            method: 'post',
            url: '/api/assignment-files/download',
            responseType: 'arraybuffer',
            data: {
              'assignment_id': this.assignmentId,
              'submission': this.assignmentFiles[currentPage - 1]['submission']
            }
          })
          this.$noty.success("The assignment file is being downloaded")
          let blob = new Blob([data], {type: 'application/pdf'})
          let link = document.createElement('a')
          link.href = window.URL.createObjectURL(blob)
          link.download = this.assignmentFiles[currentPage - 1]['original_filename']
          link.click()
        } catch (error) {
          this.$noty.error(error.message)
        }

      },
      async changePage(currentPage) {
        if (this.assignmentFiles[currentPage - 1]['submission']) {
          const {data} = await axios.post('/api/assignment-files/get-temporary-url',
            {
              'assignment_id': this.assignmentId,
              'submission': this.assignmentFiles[currentPage - 1]['submission']
            })
          this.assignmentFiles[currentPage - 1]['url'] = data
        }
      },
      getAssignmentUrl(currentPage) {
        return this.assignmentFiles[currentPage - 1]['url']
      },
      assignmentUrlExists(currentPage) {
        return (this.assignmentFiles[currentPage - 1]['url'] !== null)
      },
      async getAssignmentFiles() {
        const {data} = await axios.get(`/api/assignment-files/${this.assignmentId}`)
        console.log(data)
        this.assignmentFiles = data
      }
    }
  }
</script>

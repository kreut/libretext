<template>
  <div>
    <PageTitle v-if="canViewAssignments" title="Assignments"></PageTitle>
    <div v-if="hasAssignments">
      <b-modal
        id="modal-upload-assignment-file"
        ref="modal"
        title="Upload File"
        @ok="handleOk"
        @hidden="resetModalForms"
        ok-title="Submit"

      >
        <b-form ref="form" @submit.stop.prevent="submitUploadAssignmentFile">
          <b-form-file
            ref="assignmentFileInput"
            v-model="form.assignmentFile"
            placeholder="Choose a file or drop it here..."
            drop-placeholder="Drop file here..."
          ></b-form-file>
          <div v-if="uploading">
            <b-spinner small type="grow"></b-spinner> Uploading file...
          </div>
          <input type="hidden" class="form-control is-invalid">
          <div class="help-block invalid-feedback">{{ form.errors.get('assignmentFile')}}
          </div>

        </b-form>
      </b-modal>

      <b-table striped hover :fields="fields" :items="assignments">
        <template v-slot:cell(name)="data">
          <div class="mb-0">
            <a href="" v-on:click.prevent="getStudentView(data.item.id)">{{ data.item.name }}</a>
          </div>
        </template>
        <template v-slot:cell(files)="data">
          <div class="mb-0">
            <b-button variant="primary" v-on:click="openUploadAssignmentFileModal(data.item.id)"
                      v-b-modal.modal-upload-assignment-file>Upload File
            </b-button>
          </div>
        </template>

      </b-table>
    </div>
    <div v-else>
      <br>
      <div class="mt-4">
        <b-alert :show="showNoAssignmentsAlert" variant="warning"><a href="#" class="alert-link">This course currently
          has
          no assignments.</a></b-alert>
      </div>
    </div>
  </div>
</template>

<script>
  import axios from 'axios'
  import Form from "vform"

  const now = new Date()

  const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']
  let formatDateAndTime = value => {
    let date = new Date(value)
    return months[date.getMonth()] + ' ' + date.getDate() + ', ' + date.getFullYear() + ' ' + date.toLocaleTimeString()
  }


  export default {
    middleware: 'auth',
    data: () => ({
      form: new Form({
        assignmentFile: null,
      }),
      uploading: false,
      assignments: [],
      courseId: false,
      fields: [
        'name',
        {
          key: 'available_from',
          formatter: value => {
            return formatDateAndTime(value)
          }
        },
        {
          key: 'due',
          formatter: value => {
            return formatDateAndTime(value)
          }
        },
        'credit_given_if_at_least',
        'files'
      ],
      hasAssignments: false,
      showNoAssignmentsAlert: false,
      canViewAssignments: false
    }),
    mounted() {
      this.courseId = this.$route.params.courseId
      this.getAssignments()
    },
    methods: {
      handleOk(bvModalEvt) {
        // Prevent modal from closing
        bvModalEvt.preventDefault()
        // Trigger submit handler
        this.submitUploadAssignmentFile()
      },
      async submitUploadAssignmentFile() {
        try {
          console.log(this.form)
          this.form.errors.set('assignmentFile', null)
          this.uploading = true
          //https://stackoverflow.com/questions/49328956/file-upload-with-vue-and-laravel
          let formData = new FormData();
          formData.append('assignmentFile', this.form.assignmentFile)
          formData.append('assignmentId', this.assignmentId);
          formData.append('_method', 'put'); // add this
          const {data} = await axios.post('/api/uploads/assignment-file', formData)
          if (data.type === 'error') {
            this.form.errors.set('assignmentFile', data.message)
          } else {
            this.$noty.success(data.message)
            this.$nextTick(() => {
              this.$bvModal.hide(modalId)
            })
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
      resetModalForms() {
        // alert('reset modal')
      },
      openUploadAssignmentFileModal(assignmentId) {
        this.assignmentId = assignmentId
      },
      getStudentView(assignmentId) {
        this.$router.push(`/assignments/${assignmentId}/questions/view`)
      },
      async getAssignments() {
        try {
          const {data} = await axios.get(`/api/assignments/courses/${this.courseId}`)
          console.log(data)
          if (data.type === 'error') {
            this.$noty.error(data.message)
            return false
          }
          this.canViewAssignments = true
          this.hasAssignments = data.length > 0
          this.showNoAssignmentsAlert = !this.hasAssignments
          this.assignments = data

        } catch (error) {
          alert(error.response)
        }
      },
      metaInfo() {
        return {title: this.$t('home')}
      }
    }
  }
</script>

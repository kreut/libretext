<template>
  <div>
    <div class="row mb-4 float-right">
      <b-button v-b-modal.modal-course-details>Add Course</b-button>
    </div>
      <b-modal
        id="modal-course-details"
        ref="modal"
        title="Course Details"
        @ok="handleOk"
        @hidden="resetModal"
        ok-title="Submit"

      >
        <b-form ref="form" @submit="createCourse">
            <b-form-group
              id="name"
              label-cols-sm="4"
              label-cols-lg="3"
              label="Name"
              label-for="name"
            >
              <b-form-input
                id="name"
                v-model="form.name"
                type="text"
                :class="{ 'is-invalid': form.errors.has('name') }"
                @keydown="form.errors.clear('name')"
              >
              </b-form-input>
              <has-error :form="form" field="name"></has-error>
            </b-form-group>

            <b-form-group
              id="start_date"
              label-cols-sm="4"
              label-cols-lg="3"
              label="Start Date"
              label-for="Start Date"
            >
              <b-form-datepicker
                v-model="form.start_date"
                :min="min"
                :class="{ 'is-invalid': form.errors.has('start_date') }"
                v-on:shown="form.errors.clear('start_date')">
              </b-form-datepicker>
              <has-error :form="form" field="start_date"></has-error>
            </b-form-group>

            <b-form-group
              id="end_date"
              label-cols-sm="4"
              label-cols-lg="3"
              label="End Date"
              label-for="End Date"
            >
              <b-form-datepicker
                v-model="form.end_date"
                :min="min"
                class="mb-2"
                :class="{ 'is-invalid': form.errors.has('end_date') }"
                @click="form.errors.clear('end_date')"
                v-on:shown="form.errors.clear('end_date')">
              </b-form-datepicker>
              <has-error :form="form" field="end_date"></has-error>
            </b-form-group>
        </b-form>
      </b-modal>

    <b-modal
      id="modal-delete-course"
      ref="modal"
      title="Delete Course"
      @ok="handleDeleteCourse"
      @hidden="resetModal"
      ok-title="Delete"

    >
      <p>By deleting the course, you will also delete:</p>
      <ol>
        <li>All assignments associated with the course</li>
      <li>All submitted student responses</li>
      <li>All student grades</li>
      </ol>
      <p>Please verify that this is what you want to do by entering your password below.</p>
      <p><strong>Once a course is deleted, it can not be retrieved!</strong></p>
      <b-form ref="form" @submit="deleteCourse">

        <b-form-group
          id="password"
          label-cols-sm="4"
          label-cols-lg="3"
          label="Password"
          label-for="password"
        >
          <b-form-input
            id="password"
            v-model="deleteForm.password"
            type="text"
            :class="{ 'is-invalid': deleteForm.errors.has('password') }"
            @keydown="pdeleteForm.errors.clear('password')"
          >
          </b-form-input>
          <has-error :form="form" field="password"></has-error>
        </b-form-group>

      </b-form>
    </b-modal>

    <b-table striped hover :fields="fields" :items="courses">
      <template v-slot:cell(name)="data">
        <a :href="`/courses/${data.item.id}`">{{ data.item.name }}</a>
      </template>
      <template v-slot:cell(actions)="data">
        <div class="mb-0">
          <span class="pr-1"><b-icon icon="file-earmark-text" ></b-icon></span>
          <span class="pr-1"> <b-icon icon="file-spreadsheet" ></b-icon></span>
          <span class="pr-1" v-on:click="editCourse(data.item)"><b-icon icon="pencil" ></b-icon></span>
          <b-icon icon="trash" v-b-modal.modal-delete-course></b-icon>
        </div>
      </template>
    </b-table>
  </div>
</template>

<script>
  import axios from 'axios'
  import Form from "vform";
let formatDate = value => {
  let date_pieces = value.split('-')
  let month = date_pieces[1]
  let day = date_pieces[2].split(' ')[0]//get rid of the time piece 2020-06-21 00:00:00
  let year = date_pieces[0]
  return month + '-' + day + '-' + year
}

  const now = new Date()
  export default {
    middleware: 'auth',
    data: () => ({
      fields: [
        {
          key: 'name',
          label: 'Course'
        },
        {
          key: 'start_date',
          formatter: value => {
            return formatDate(value)
          }
        },
        {
          key: 'end_date',
          formatter: value => {
            return formatDate(value)
          }
        },
        'actions'
      ],
      courses: [],
      courseId: false, //if there's a courseId it's an update
      min: new Date(now.getFullYear(), now.getMonth(), now.getDate()),
      form: new Form({
        name: '',
        start_date: '',
        end_date: ''
      }),
      deleteForm: new Form({
        password: ''
      }),
    }),
    mounted() {
      this.getCourses();

    },
    methods: {
      editCourse(course) {
        this.courseId = course.id
        this.$bvModal.show('modal-course-details')
        this.form.name = course.name
        this.form.start_date = course.start_date
        this.form.end_date = course.end_date
      },
      resetModal() {
        this.form.name = ''
        this.form.start_date = ''
        this.form.end_date = ''
        this.courseId = false
        this.form.errors.clear()
      },
      openDeleteCourseModal(course){


      },
      handleOk(bvModalEvt) {
        // Prevent modal from closing
        bvModalEvt.preventDefault()
        // Trigger submit handler
        this.createCourse(bvModalEvt)
      },
        async createCourse(evt) {
          try {
            let endpoint = (!this.courseId) ? '/api/courses' : '/api/courses/' + this.courseId
            const { data } = await this.form.post(endpoint)
            this.getCourses()
            this.resetModal()
            // Hide the modal manually
            this.$nextTick(() => {
              this.$bvModal.hide('modal-course-details')
            })

          } catch (error){
            console.log(error)
          }
      },
      getCourses() {
        try {
          axios.get('/api/courses').then(
            response => this.courses = response.data
          )
        } catch (error) {
          alert(error.message)

        }
      }
    },
    metaInfo() {
      return {title: this.$t('home')}
    }
  }
</script>

<template>
  <div>
    <div class="row mb-4 float-right">
      <b-button v-b-modal.modal-add-course>Add Course</b-button>
    </div>
      <b-modal
        id="modal-add-course"
        ref="modal"
        title="Add Course"
        @show="resetModal"
        @hidden="resetModal"
        @ok="handleOk"
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

    <b-table striped hover :fields="fields" :items="courses">
      <template v-slot:cell(name)="data">
        <a :href="`/courses/${data.item.id}`">{{ data.item.name }}</a>
      </template>
      <template v-slot:cell(actions)="data">
        <div class="mb-0">
          <span class="pr-1"><b-icon icon="file-earmark-text" ></b-icon></span>
          <span class="pr-1"> <b-icon icon="file-spreadsheet" ></b-icon></span>
          <span class="pr-1" v-on:click="editCourse(data.item)"><b-icon icon="pencil" ></b-icon></span>
          <b-icon icon="trash" ></b-icon>
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
      courseAction: 'Add',
      min: new Date(now.getFullYear(), now.getMonth(), now.getDate()),
      form: new Form({
        name: '',
        start_date: '',
        end_date: ''
      }),
    }),
    mounted() {
      this.getCourses();

    },
    methods: {
      editCourse(course) {
        this.courseAction = 'Update'
        this.$bvModal.show('modal-add-course')
        this.form.name = course.name
        this.form.start_date = course.start_date
        this.form.end_date = course.end_date
      },
      resetModal() {
        this.form.name = ''
        this.form.start_date = ''
        this.form.end_date = ''
      },
      handleOk(bvModalEvt) {
        // Prevent modal from closing
        bvModalEvt.preventDefault()
        // Trigger submit handler
        this.createCourse(bvModalEvt)
      },
        async createCourse(evt) {
          try {
            let endpoint = (this.courseAction === 'Add') ? '/api/courses' : 'sdfdsf'
            const { data } = await this.form.post(endpoint)
            this.courseAction = 'Add' //change back from edit
            this.getCourses()
            this.resetModal()
            // Hide the modal manually
            this.$nextTick(() => {
              this.$bvModal.hide('modal-add-course')
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
          alert(error.response)
        }
      }
    },
    metaInfo() {
      return {title: this.$t('home')}
    }
  }
</script>

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
    </b-table>
  </div>
</template>

<script>
  import axios from 'axios'
  import Form from "vform";

  const now = new Date()
  export default {
    middleware: 'auth',
    data: () => ({
      fields: [
        {key: 'name', label: 'Course'},
        'start_date',
        'end_date'
      ],
      courses: [],
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
            const { data } = await this.form.post('/api/courses')
            console.log(this.courses)
            resetModal()
            // Hide the modal manually
            this.$nextTick(() => {
              this.$bvModal.hide('modal-add-course')
            })

          } catch (error){
            console.info(error.response.data.errors)
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

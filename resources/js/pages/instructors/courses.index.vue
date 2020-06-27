<template>
  <div>
    <div class="row mb-4 float-right">
      <b-button v-b-modal.modal-prevent-closing>Add Course</b-button>
    </div>
      <b-modal
        id="modal-prevent-closing"
        ref="modal"
        title="Submit Your Name"
        @show="resetModal"
        @hidden="resetModal"
        @ok="handleOk"
      >
        <form ref="form" @submit.stop.prevent="handleSubmit">
          <b-form-group
            :state="nameState"
            label="Name"
            label-for="name-input"
            invalid-feedback="Name is required"
          >
            <b-form-input
              id="name-input"
              v-model="name"
              :state="nameState"
              required
            ></b-form-input>
          </b-form-group>
        </form>
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

  export default {
    middleware: 'auth',
    data: () => ({
      fields: [
        {key: 'name', label: 'Course'},
        'start_date',
        'end_date'
      ],
      courses: [],
      name: '',
      nameState: null,
    }),
    mounted() {
      this.getCourses();

    },
    methods: {
      checkFormValidity() {
        const valid = this.$refs.form.checkValidity()
        this.nameState = valid
        return valid
      },
      resetModal() {
        this.name = ''
        this.nameState = null
      },
      handleOk(bvModalEvt) {
        // Prevent modal from closing
        bvModalEvt.preventDefault()
        // Trigger submit handler
        this.handleSubmit()
      },
      handleSubmit() {
        // Exit when the form isn't valid
        if (!this.checkFormValidity()) {
          return
        }
        //do something...
        // Hide the modal manually
        this.$nextTick(() => {
          this.$bvModal.hide('modal-prevent-closing')
        })
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

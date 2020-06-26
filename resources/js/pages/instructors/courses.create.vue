<template>
  <div>
    <b-form @submit="createCourse" @reset="onReset" v-if="show">
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
        >
        </b-form-input>
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
          class="mb-2">
        </b-form-datepicker>
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
          class="mb-2">
        </b-form-datepicker>
      </b-form-group>


      <b-button type="submit" variant="primary">Submit</b-button>
      <b-button type="reset" variant="danger">Reset</b-button>
    </b-form>
  </div>
</template>


<script>
  import Form from 'vform'

  export default {
    middleware: 'auth',

    metaInfo() {
      return {title: this.$t('My Courses')}
    },
    data() {
      return {
        form: new Form({
          name: '',
          start_date: '',
          end_date: ''
        }),
        show: true
      }
    },
    methods: {
      async createCourse(evt) {
        evt.preventDefault()

        const {data} = await this.form.post('/api/courses')
        console.log(data);
      },
      onReset(evt) {
        evt.preventDefault()
        // Reset our form values
        this.form.name = ''
        // Trick to reset/clear native browser form validation state
        this.show = false
        this.$nextTick(() => {
          this.show = true
        })
      }
    }
  }
</script>

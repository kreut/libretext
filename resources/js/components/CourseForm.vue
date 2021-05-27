<template>
  <div>
    <b-tooltip target="public_tooltip"
               delay="250"
    >
      Public courses can be imported by other instructors; non-public can only be imported by you. Note that student
      grades will never be made public nor copied from a course.
    </b-tooltip>
    <b-tooltip target="school_tooltip"
               delay="250"
    >
      Adapt keeps a comprehensive list of colleges and universities, using the school's full name.  So, to find UC-Davis, you
      can start typing University of California-Los Angeles. In general, any word within your school's name will lead you to your school.  If you still can't
      find it, then please contact us.
    </b-tooltip>
    <b-form ref="form">
      <b-form-group
        id="school"
        label-cols-sm="4"
        label-cols-lg="3"
        label-for="school"
      >
        <template slot="label">
          School
          <span id="school_tooltip">
            <b-icon class="text-muted" icon="question-circle"/></span>
        </template>
        <vue-bootstrap-typeahead
          ref="schoolTypeAhead"
          v-model="form.school"
          :data="schools"
          placeholder="Not Specified"
          :class="{ 'is-invalid': form.errors.has('school') }"
          @keydown="form.errors.clear('school')"
        />
        <has-error :form="form" field="school"/>
      </b-form-group>
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
        />
        <has-error :form="form" field="name"/>
      </b-form-group>
      <div v-if="'section' in form">
        <b-form-group
          id="section"
          label-cols-sm="4"
          label-cols-lg="3"
          label="Section"
          label-for="section"
        >
          <b-form-input
            id="name"
            v-model="form.section"
            type="text"
            :class="{ 'is-invalid': form.errors.has('section') }"
            @keydown="form.errors.clear('section')"
          />
          <has-error :form="form" field="section"/>
        </b-form-group>
      </div>
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
          @shown="form.errors.clear('start_date')"
        />
        <has-error :form="form" field="start_date"/>
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
          @shown="form.errors.clear('end_date')"
        />
        <has-error :form="form" field="end_date"/>
      </b-form-group>
      <b-form-group
        id="public"
        label-cols-sm="4"
        label-cols-lg="3"
        label-for="Public"
      >
        <template slot="label">
          Public
          <span id="public_tooltip">
            <b-icon class="text-muted" icon="question-circle"/></span>
        </template>
        <b-form-radio-group v-model="form.public" stacked>
          <b-form-radio name="public" value="1">
            Yes
          </b-form-radio>

          <b-form-radio name="public" value="0">
            No
          </b-form-radio>
        </b-form-radio-group>
      </b-form-group>
    </b-form>
  </div>
</template>

<script>
import VueBootstrapTypeahead from 'vue-bootstrap-typeahead'
import axios from 'axios'

const now = new Date()
export default {
  name: 'CourseForm',
  components: {
    VueBootstrapTypeahead
  },
  props: {
    form: { type: Object, default: null }
  },
  data: () => ({
    schools: [],
    min: new Date(now.getFullYear(), now.getMonth(), now.getDate())
  }),
  mounted () {
    if (this.form.school) {
      this.$refs.schoolTypeAhead.inputValue = this.form.school
    }
    this.getSchools()
  },
  methods: {
    async getSchools () {
      try {
        const { data } = await axios.get(`/api/schools`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.schools = data.schools
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }
}
</script>

<style scoped>

</style>

<template>
  <div>
    <b-tooltip target="public_tooltip"
               delay="250"
    >
      Public courses can be imported by other instructors; non-public can only be imported by you.  Note that student grades will never be made public nor copied from a course.
    </b-tooltip>
    <b-form ref="form">
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
        <has-error :form="form" field="name" />
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
          @shown="form.errors.clear('start_date')"
        />
        <has-error :form="form" field="start_date" />
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
        <has-error :form="form" field="end_date" />
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
            <b-icon class="text-muted" icon="question-circle" /></span>
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
const now = new Date()
export default {
  name: 'CourseForm',
  props: {
    form: { type: Object, default: null }
  },
  data: () => ({

    min: new Date(now.getFullYear(), now.getMonth(), now.getDate())
  })
}
</script>

<style scoped>

</style>

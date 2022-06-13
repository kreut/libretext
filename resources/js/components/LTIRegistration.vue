<template>
  <div>
    <b-form-group
      label-cols-sm="4"
      label-cols-lg="3"
      label-for="developer_key_id"
    >
      <template v-slot:label>
        Developer Key ID*
      </template>
      <b-form-input
        id="developer_key_id"
        v-model="form.developer_key_id"
        type="text"
        placeholder="Example. 10203900029"
        required
        :class="{ 'is-invalid': form.errors.has('developer_key_id') }"
        @keydown="form.errors.clear('developer_key_id')"
      />
      <has-error :form="form" field="developer_key_id"/>
    </b-form-group>
    <b-form-group
      v-show="showCampusId"
      label-cols-sm="4"
      label-cols-lg="3"
      label-for="campus_id"
    >
      <template v-slot:label>
        Campus Id
      </template>
      <b-form-input
        id="campus_id"
        v-model="form.campus_id"
        type="text"
        :class="{ 'is-invalid': form.errors.has('campus_id') }"
        @keydown="form.errors.clear('campus_id')"
      />
      <has-error :form="form" field="campus_id"/>
    </b-form-group>
    <b-form-group
      label-cols-sm="4"
      label-cols-lg="3"
      label-for="canvas_url"
    >
      <template v-slot:label>
        Canvas URL*
      </template>
      <b-form-input
        id="canvas_url"
        v-model="form.url"
        type="text"
        placeholder="https://my-canvas-url.instructure.com"
        required
        :class="{ 'is-invalid': form.errors.has('url') }"
        @keydown="form.errors.clear('url')"
      />
      <has-error :form="form" field="url"/>
    </b-form-group>
    <b-form-group
      v-show="showSchools"
      label-cols-sm="4"
      label-cols-lg="3"
      label-for="schools"
    >
      <template v-slot:label>
        School*
      </template>
      <autocomplete
        ref="schoolSearch"
        :search="searchBySchool"
        @submit="selectSchool"
      />
      <input type="hidden" class="form-control is-invalid">
      <div class="help-block invalid-feedback">
        {{ form.errors.get('school') }}
      </div>
    </b-form-group>
    <b-form-group
      label-cols-sm="4"
      label-cols-lg="3"
      label-for="admin_name"
    >
      <template v-slot:label>
        Admin Name*
      </template>
      <b-form-input
        id="admin_name"
        v-model="form.admin_name"
        type="text"
        placeholder=""
        required
        :class="{ 'is-invalid': form.errors.has('admin_name') }"
        @keydown="form.errors.clear('admin_name')"
      />
      <has-error :form="form" field="admin_name"/>
    </b-form-group>
    <b-form-group
      label-cols-sm="4"
      label-cols-lg="3"
      label-for="admin_email"
    >
      <template v-slot:label>
        Admin Email*
      </template>
      <b-form-input
        id="admin_email"
        v-model="form.admin_email"
        type="text"
        placeholder=""
        required
        :class="{ 'is-invalid': form.errors.has('admin_email') }"
        @keydown="form.errors.clear('admin_name')"
      />
      <has-error :form="form" field="admin_email"/>
    </b-form-group>
    <b-alert :show="!showSchools">
      <span class="font-weight-bold">Add the list of schools to the LTI schools table so that the LMS options will pop
      up for those users.</span>
    </b-alert>
  </div>
</template>

<script>
import Autocomplete from '@trevoreyre/autocomplete-vue'
import '@trevoreyre/autocomplete-vue/dist/style.css'
import axios from 'axios'

export default {
  name: 'LTIRegistration',
  components: { Autocomplete },
  props: {
    form: {
      type: Object,
      default: function () {
        return {}
      }
    },
    showCampusId: {
      type: Boolean,
      default: false
    },
    showSchools: {
      type: Boolean,
      default: true
    }
  },
  data: () => ({
    school: ''
  }),
  mounted () {
    this.schools = this.getSchools()
    this.form.school = 'fake school name'
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
    },
    selectSchool (selectedSchool) {
      this.form.school = selectedSchool
    },
    searchBySchool (input) {
      if (input.length < 1) {
        return []
      }
      let matches = this.schools.filter(school => school.toLowerCase().includes(input.toLowerCase()))
      let schools = []
      if (matches) {
        for (let i = 0; i < matches.length; i++) {
          schools.push(matches[i])
        }
        schools.sort()
      }
      return schools
    }
  }
}
</script>

<style scoped>

</style>

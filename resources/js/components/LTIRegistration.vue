<template>
  <div>
    <b-form-group
      label-cols-sm="4"
      label-cols-lg="3"
      label-for="developer_key_id"
    >
      <template slot="label">
        Developer Key ID*
      </template>
      <b-form-input
        id="developer_key_id"
        v-model="form.developer_key_id"
        type="text"
        placeholder="Example. 10203900029"
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
      <template slot="label">
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
      <template slot="label">
        Canvas URL*
      </template>
      <b-form-input
        id="canvas_url"
        v-model="form.url"
        type="text"
        placeholder="https://my-canvas-url.instructure.com"
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
      <template slot="label">
        School(s)*
      </template>
      <b-form-input
        id="schools"
        v-model="form.schools"
        type="text"
        placeholder="Comma separated list of schools linked to your Canvas instance"
        :class="{ 'is-invalid': form.errors.has('schools') }"
        @keydown="form.errors.clear('schools')"
      />
      <has-error :form="form" field="schools"/>
    </b-form-group>
    <b-form-group
      label-cols-sm="4"
      label-cols-lg="3"
      label-for="admin_name"
    >
      <template slot="label">
        Admin Name*
      </template>
      <b-form-input
        id="admin_name"
        v-model="form.admin_name"
        type="text"
        placeholder=""
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
      <template slot="label">
        Admin Email*
      </template>
      <b-form-input
        id="admin_email"
        v-model="form.admin_email"
        type="text"
        placeholder=""
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
export default {
  name: 'LTIRegistration',
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
  }
}
</script>

<style scoped>

</style>

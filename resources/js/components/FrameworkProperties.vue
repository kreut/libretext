<template>
  <div>
    <div v-if="!isFrameworkOwner">
      <ul style="list-style: none">
        <li><span class="font-weight-bold">Title:</span> {{ frameworkForm.title }}</li>
        <li><span class="font-weight-bold">Type: </span>{{ frameworkForm.descriptor_type }}</li>
        <li><span class="font-weight-bold">Description: </span>{{ frameworkForm.description }}</li>
        <li><span class="font-weight-bold">Author: </span>{{ frameworkForm.author }}</li>
        <li><span class="font-weight-bold">License: </span>{{ licenseOptions.find(item => frameworkForm.license ===item.value).text }}</li>
        <li v-if="frameworkForm.license_version">
          <span class="font-weight-bold">License Version:</span> {{ licenseVersionOptions.find(item => frameworkForm.license_version ===item.value).text }}
        </li>
        <li><span class="font-weight-bold">Source URL: </span>{{ frameworkForm.source_url }}</li>
      </ul>
    </div>
    <div v-if="isFrameworkOwner">
      <b-form>
        <RequiredText/>
        <b-form-group
          label-cols-sm="2"
          label-cols-lg="2"
          label-for="title"
        >
          <template v-slot:label>
            Title*
          </template>
          <b-form-input
            v-if="isFrameworkOwner"
            id="name"
            v-model="frameworkForm.title"
            required
            type="text"
            :class="{ 'is-invalid': frameworkForm.errors.has('title') }"
            @keydown="frameworkForm.errors.clear('title')"
          />
          <has-error :form="frameworkForm" field="title"/>
        </b-form-group>
        <b-form-group
          label-cols-sm="2"
          label-cols-lg="2"
          label-for="type"
          label="Type*"
        >
          <b-form-select v-model="frameworkForm.descriptor_type"
                         style="width:220px"
                         title="license"
                         size="sm"
                         :options="typeOptions"
                         class="mt-2  mr-2"
                         :class="{ 'is-invalid': frameworkForm.errors.has('descriptor_type') }"
                         @change="frameworkForm.errors.clear('descriptor_type')"
          />
          <has-error :form="frameworkForm" field="descriptor_type"/>
        </b-form-group>
        <b-form-group
          label-cols-sm="3"
          label-cols-lg="2"
          label-for="description"
          label="Description*"
        >
          <b-form-textarea
            id="description"
            v-model="frameworkForm.description"
            style="margin-bottom: 23px"
            rows="2"
            max-rows="2"
            :class="{ 'is-invalid': frameworkForm.errors.has('description') }"
            @keydown="frameworkForm.errors.clear('description')"
          />
          <has-error :form="frameworkForm" field="description"/>
        </b-form-group>
        <b-form-group
          label-cols-sm="2"
          label-cols-lg="2"
          label-for="author"
          label="Author*"
        >
          <b-form-input
            id="author"
            v-model="frameworkForm.author"
            style="width:500px"
            :class="{ 'is-invalid': frameworkForm.errors.has('author') }"
            @keydown="frameworkForm.errors.clear('author')"
          />
          <has-error :form="frameworkForm" field="author"/>
        </b-form-group>
        <b-form-group
          label-cols-sm="2"
          label-cols-lg="2"
          label-for="license"
          label="License*"
        >
          <b-form-select v-model="frameworkForm.license"
                         style="width:220px"
                         title="license"
                         size="sm"
                         :options="licenseOptions"
                         class="mt-2  mr-2"
                         :class="{ 'is-invalid': frameworkForm.errors.has('license') }"
                         @change="frameworkForm.errors.clear('license');updateLicenseVersion($event)"
          />
          <has-error :form="frameworkForm" field="license"/>
        </b-form-group>
        <b-form-group
          v-if="licenseVersionOptions.length"
          label-cols-sm="3"
          label-cols-lg="2"
          label-for="license_version"
          label="License Version"
        >
            <b-form-select v-model="frameworkForm.license_version"
                           style="width:100px"
                           title="license version"
                           required
                           size="sm"
                           class="mt-2"
                           :options="licenseVersionOptions"
            />
        </b-form-group>
        <b-form-group
          label-cols-sm="3"
          label-cols-lg="2"
          label-for="source_url"
          label="Source URL*"
        >
          <b-form-input
            id="source_url"
            v-model="frameworkForm.source_url"
            size="sm"
            type="text"
            :class="{ 'is-invalid': frameworkForm.errors.has('source_url') }"
            class="mt-2"
            @keydown="frameworkForm.errors.clear('source_url')"
          />
          <has-error :form="frameworkForm" field="source_url"/>

        </b-form-group>
      </b-form>
    </div>
  </div>
</template>

<script>
import { defaultLicenseVersionOptions, licenseOptions, updateLicenseVersions } from '~/helpers/Licenses'

export default {
  name: 'FrameworkProperties',
  props: {
    isFrameworkOwner: {
      type: Boolean,
      default: false
    },
    frameworkForm: {
      type: Object,
      default: () => {
      }
    }
  },
  data: () => ({
    typeOptions: [
      { text: 'Please choose a type', value: null },
      { text: 'Keyword', value: 'keyword' },
      { text: 'Skills', value: 'skills' },
      { text: 'Taxonomy', value: 'taxonomy' },
      { text: 'Concept', value: 'concept' },
      { text: 'Learning Outcome', value: 'learning outcome' },
      { text: 'Learning Objective', value: 'learning objective' }
    ],
    licenseOptions: licenseOptions,
    defaultLicenseVersionOptions: defaultLicenseVersionOptions,
    licenseVersionOptions: []
  }),
  created () {
    this.updateLicenseVersions = updateLicenseVersions
  },
  mounted () {
    this.updateLicenseVersion(this.frameworkForm.license)
  },
  methods: {
    updateLicenseVersion (license) {
      this.frameworkForm.license_version = this.updateLicenseVersions(license)
    }
  }
}
</script>

<style scoped>

</style>

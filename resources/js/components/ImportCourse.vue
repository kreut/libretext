<template>
  <span>
    <b-modal
      :id="`modal-import-course-as-beta-${openCourse.id}`"
      ref="modalImportCourseAsBeta"
      title="Import As Beta"
    >
      <ImportAsBetaText class="pb-2"/>
      <b-form-group
        id="beta"
        label-cols-sm="7"
        label-cols-lg="6"
        label-for="beta"
        label="Import as a Beta Course"
      >
        <b-form-radio-group v-model="courseToImportForm.import_as_beta" class="mt-2">
          <b-form-radio name="beta" value="1">
            Yes
          </b-form-radio>
          <b-form-radio name="beta" value="0">
            No
          </b-form-radio>
        </b-form-radio-group>
      </b-form-group>
      <template #modal-footer>
        <span v-if="importingCourse" class="float-right">
          <b-spinner small type="grow"/>
          Processing...
        </span>
        <b-button
          size="sm"
          :disabled="importingCourse"
          class="float-right"
          @click="$bvModal.hide(`modal-import-course-as-beta-${openCourse.id}`)"
        >
          Cancel
        </b-button>
        <b-button
          variant="primary"
          :disabled="importingCourse"
          size="sm"
          class="float-right"
          @click="handleImportCourse"
        >
          Import
        </b-button>
      </template>
    </b-modal>
    <b-button
      v-if="!icon"
      :variant="buttonClass"
      size="sm"
      :disabled="importingCourse"
      :aria-label="`Import the course ${openCourse.name}`"
      :class="oneButtonPerRow ? 'mb-2' :''"
      @click="initImportCourse()"
    >
      Import Course
    </b-button>
    <a v-if="!importingCourse"
       :id="`import-course-${openCourse.id}`"
       href="#"
       class="pr-1"
       @click="initImportCourse()"
    >
      <b-icon v-if="icon"
              icon="download"
              class="text-muted"
      />
    </a>
     <b-tooltip :target="`import-course-${openCourse.id}`">
       Import {{ openCourse.name }}</b-tooltip>
    <span v-if="importingCourse && !importAsBetaOpen">
      <b-spinner small type="grow"/>
      <span v-if="!icon">Processing...</span>
    </span>
  </span>
</template>

<script>
import ImportAsBetaText from '~/components/ImportAsBetaText'
import Form from 'vform'

export default {
  components: {
    ImportAsBetaText
  },
  props: {
    icon: {
      type: Boolean,
      default: false
    },
    buttonClass: {
      type: String,
      default: 'outline-primary'
    },
    oneButtonPerRow: {
      type: Boolean,
      default: false
    },
    openCourse: {
      type: Object,
      default: () => {
      }
    }
  },
  data: () => ({
    importAsBetaOpen: false,
    importingCourse: false,
    idOfCourseToImport: 0,
    courseToImportForm: new Form({
      import_as_beta: 0
    })
  }),
  methods: {
    initImportCourse () {
      if (this.importingCourse) {
        return false
      }
      this.idOfCourseToImport = this.openCourse.id
      this.openCourse.alpha
        ? this.openImportCourseAsBetaModal()
        : this.handleImportCourse()
    },
    openImportCourseAsBetaModal () {
      this.importAsBetaOpen = true
      this.$bvModal.show(`modal-import-course-as-beta-${this.openCourse.id}`)
    },
    async handleImportCourse () {
      this.importingCourse = true
      try {
        this.courseToImportForm.action = 'import'
        const { data } = await this.courseToImportForm.post(`/api/courses/import/${this.idOfCourseToImport}`)
        this.$bvModal.hide(`modal-import-course-as-beta-${this.openCourse.id}`)
        this.importAsBetaOpen = false
        this.courseToImportForm.import_as_beta = 0 // reset
        this.$noty[data.type](data.message)
        this.importingCourse = false
        if (data.type === 'error') {
          return false
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.$bvModal.hide(`modal-import-course-as-beta-${this.openCourse.id}`)
      this.courseToImportForm.import_as_beta = 0
      this.importingCousre = false
      this.importAsBetaOpen = false
    }
  }
}
</script>

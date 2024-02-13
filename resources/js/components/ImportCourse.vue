<template>
  <span>
    <ImportingCourseModal :importing-course="importingCourse"
                          :importing-course-message="importingCourseMessage"
                          :imported-course="openCourse"
                          :import-actioning="importActioning"
    />
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
    <a
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
  </span>
</template>

<script>
import ImportAsBetaText from '~/components/ImportAsBetaText'
import Form from 'vform'
import { mapGetters } from 'vuex'
import ImportingCourseModal from './ImportingCourseModal.vue'
import { initCentrifuge } from '~/helpers/Centrifuge'

export default {
  components: {
    ImportingCourseModal,
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
    centrifuge: {},
    importActioning: '',
    importingCourseKey: 0,
    importingCourseMessage: { message: '', type: '' },
    modalImportingCourseId: '',
    importAsBetaOpen: false,
    importingCourse: false,
    idOfCourseToImport: 0,
    courseToImportForm: new Form({
      import_as_beta: 0
    })
  }),
  computed: mapGetters({
    authenticated: 'auth/check',
    user: 'auth/user'
  }),
  beforeDestroy () {
    try {
      if (this.centrifuge) {
        this.centrifuge.disconnect()
      }
    } catch (error) {
      // won't be a function for all the other ones that haven't been defined on the page
    }
  },
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
    async courseImportedCopied (ctx) {
      this.importingCourseMessage = ctx.data
      console.log(this.importingCourseMessage)
      this.importingCourse = false
      this.centrifuge.disconnect()
    },
    async handleImportCourse () {
      this.centrifuge = await initCentrifuge()
      const sub = this.centrifuge.newSubscription(`import-copy-course-${this.user.id}`)
      const courseImportedCopied = async (ctx) => {
        this.importingCourseMessage = ctx.data
        this.importingCourse = false
        this.centrifuge.disconnect()
        await this.getCourses()
      }
      sub.on('publication', function (ctx) {
        courseImportedCopied(ctx)
      }).subscribe()
      this.importingCourse = true
      try {
        this.courseToImportForm.action = 'import'
        this.importActioning = this.courseToImportForm.action === 'import' ? 'Importing' : 'Copying'
        this.courseToImportForm.shift_dates = 0
        const { data } = await this.courseToImportForm.post(`/api/courses/import/${this.idOfCourseToImport}`)
        this.$bvModal.hide(`modal-import-course-as-beta-${this.openCourse.id}`)
        this.importAsBetaOpen = false
        this.courseToImportForm.import_as_beta = 0 // reset

        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.$bvModal.hide(`modal-import-course-as-beta-${this.openCourse.id}`)
      this.$bvModal.show(`modal-importing-course-${this.openCourse.id}`)
      this.courseToImportForm.import_as_beta = 0
      this.importAsBetaOpen = false
    }
  }
}
</script>

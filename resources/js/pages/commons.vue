<template>
  <div>
    <b-modal
      id="modal-import-course-as-beta"
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
          class="float-right"
          @click="$bvModal.hide('modal-import-course-as-beta')"
        >
          Cancel
        </b-button>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="handleImportCourse"
        >
          Import
        </b-button>
      </template>
    </b-modal>
    <b-modal
      id="modal-assignments"
      ref="modalAssignments"
      title="Assignments"
      hide-footer
    >
      <b-table striped
               hover
               responsive="true"
               :no-border-collapse="true"
               :items="assignments"
               :fields="fields"
      >
        <template v-slot:cell(description)="data">
          {{ data.item.description ? data.item.description : 'None provided' }}
        </template>
      </b-table>
    </b-modal>
    <PageTitle title="Commons"/>
    <div class="vld-parent">
      <loading :active.sync="isLoading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"
      />
      <b-container>
        <b-row>
          <b-card-group v-for="commonsCourse in commonsCourses" :key="commonsCourse.id" class="col-6 pb-5">
            <b-card>
              <template #header>
                <h5 class="mb-0 font-italic">
                  {{ commonsCourse.name }}
                </h5>
              </template>
              <b-card-text>
                <span class="font-weight-bold font-italic">Description: </span>
                {{ commonsCourse.description ? commonsCourse.description : 'None provided' }}
              </b-card-text>

              <b-button variant="primary" size="sm" @click="openAssignmentsModal(commonsCourse.id)">
                View Assignments
              </b-button>
              <b-button v-if="user && user.role === 2" variant="outline-primary" size="sm"
                        @click="IdOfCourseToImport = commonsCourse.id;commonsCourse.alpha ? openImportCourseAsBetaModal() : handleImportCourse()"
              >
                Import Course
              </b-button>
            </b-card>
          </b-card-group>
        </b-row>
      </b-container>
    </div>
  </div>
</template>

<script>
import { mapGetters } from 'vuex'
import axios from 'axios'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import Form from 'vform'
import ImportAsBetaText from '~/components/ImportAsBetaText'

export default {
  components: {
    Loading,
    ImportAsBetaText
  },
  data: () => ({
    IdOfCourseToImport: 0,
    courseToImportForm: new Form({
      import_as_beta: 0
    }),
    isLoading: true,
    commonsCourses: [],
    assignments: [],
    fields: [
      'name',
      'description'
    ]
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  mounted () {
    this.getCommonsCourses()
  },
  methods: {
    openImportCourseAsBetaModal () {
      this.$bvModal.show('modal-import-course-as-beta')
    },
    async handleImportCourse () {
      try {
        const { data } = await this.courseToImportForm.post(`/api/courses/import/${this.IdOfCourseToImport}`)
        this.$bvModal.hide('modal-import-course-as-beta')
        this.courseToImportForm.import_as_beta = 0 // reset
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          return false
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.$bvModal.hide('modal-import-course-as-beta')
      this.courseToImportForm.import_as_beta = 0
    },
    async openAssignmentsModal (courseId) {
      try {
        const { data } = await axios.get(`/api/assignments/commons/${courseId}`)
        if (data.type !== 'success') {
          this.$noty[data.type](data.message)
          return false
        }
        this.assignments = data.assignments
        this.$bvModal.show('modal-assignments')
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async getCommonsCourses () {
      try {
        const { data } = await axios.get(`/api/courses/commons`)
        if (data.type !== 'success') {
          this.isLoading = false
          this.$noty[data.type](data.message)
          return false
        }
        this.commonsCourses = data.commons_courses
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.isLoading = false
    }
  }
}
</script>

<style scoped>

</style>

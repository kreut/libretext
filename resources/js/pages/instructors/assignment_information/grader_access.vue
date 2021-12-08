<template>
  <div>
    <div class="vld-parent">
      <loading :active.sync="isLoading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"
      />
      <div v-if="!isLoading">
        <PageTitle title="Grader Access"/>
        <div v-if="assignmentGraderAccess.length">
          <p>
            Override grader access using the table below. This can be helpful for courses with graders who have access
            to
            certain sections
            in general, but require access to all sections for exams where instructors might want graders to grade
            specific questions for all students.
          </p>
          <b-form-group
            id="assignment_level_actions"
            label-cols-sm="4"
            label-cols-lg="3"
            label="Assignment Level Actions"
            label-for="Assignment Level Actions"
          >
            <b-form-radio-group
              v-model="allGraderAccessLevel"
              stacked
              @change="updateAllGraderAccessLevels($event)"
            >
              <b-form-radio name="all_grader_access_level" value="1">
                Give all graders full access
              </b-form-radio>
              <b-form-radio name="all_grader_access_level" value="0">
                Remove access for all graders
              </b-form-radio>
              <b-form-radio name="all_grader_access_level" value="-1">
                Return all graders to their default access levels
              </b-form-radio>
            </b-form-radio-group>
          </b-form-group>
          <b-table
            v-if="assignmentGraderAccess.length"
            aria-label="Assignment Grader Access"
            striped
            hover
            :no-border-collapse="true"
            :fields="fields"
            :items="assignmentGraderAccess"
          >
            <template v-slot:cell(grader)="data">
              {{ data.item.name }}
            </template>
            <template v-slot:cell(access_level)="data">
              <b-form-radio-group
                v-model="data.item.access_level"
                stacked
                @change="updateGraderAccessLevel(data.item, $event)"
              >
                <b-form-radio name="access_level" value="1">
                  All sections
                </b-form-radio>
                <b-form-radio name="access_level" value="0">
                  No sections
                </b-form-radio>
                <b-form-radio name="access_level" value="-1">
                  Default sections
                </b-form-radio>
              </b-form-radio-group>
            </template>
            <template v-slot:cell(sections)="data">
              {{ formatSections(data.item.access_level, data.item.sections) }}
            </template>
          </b-table>
        </div>

        <b-alert :show="!assignmentGraderAccess.length" variant="info">
          <span class="font-weight-bold">You currently have no graders in this course.</span>
        </b-alert>
      </div>
    </div>
  </div>
</template>
<script>
import axios from 'axios'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import { mapGetters } from 'vuex'

export default {
  middleware: 'auth',
  components: {
    Loading
  },
  metaInfo () {
    return { title: 'Assignment Grader Access' }
  },
  data: () => ({
    allGraderAccessLevel: null,
    isLoading: true,
    assignmentId: 0,
    assignmentGraderAccess: [],
    fields: [
      {
        key: 'grader',
        isRowHeader: true
      },
      'access_level',
      'sections'
    ]
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  mounted () {
    this.assignmentId = this.$route.params.assignmentId
    this.getAssignmentGraderAccess()
  },
  methods: {
    async updateAllGraderAccessLevels (accessLevel) {
      try {
        const { data } = await axios.patch(`/api/assignment-grader-access/${this.assignmentId}/${parseInt(accessLevel)}`)
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        await this.getAssignmentGraderAccess()
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    formatSections (accessLevel, sections) {
      switch (parseInt(accessLevel)) {
        case (-1):
          return Object.values(sections).join(', ')
        case (1):
          return 'All sections'
        case (0):
          return 'No sections'
      }
    },
    async updateGraderAccessLevel (graderInfo, accessLevel) {
      try {
        const { data } = await axios.patch(`/api/assignment-grader-access/${this.assignmentId}/${graderInfo.user_id}/${parseInt(accessLevel)}`)
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        graderInfo.sections = data.sections
        console.log(graderInfo)
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async getAssignmentGraderAccess () {
      try {
        const { data } = await axios.get(`/api/assignment-grader-access/${this.assignmentId}`)
        this.isLoading = false
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.assignmentGraderAccess = data.assignment_grader_access
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.isLoading = false
    }
  }
}
</script>

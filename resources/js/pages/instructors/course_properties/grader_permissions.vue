<template>
  <div>
    <b-modal
      id="modal-no-access"
      ref="modal"
      title="Remove access"
    >
      Would you like to remove all grader access to all of the assignments?
      <template #modal-footer>
        <span v-if="processing">
          <b-spinner small type="grow"/>
          Processing...
        </span>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide('modal-no-access')"
        >
          Cancel
        </b-button>
        <b-button
          variant="danger"
          size="sm"
          class="float-right"
          @click="handleGlobalCourseAccess(0)"
        >
          Yes, remove all access!
        </b-button>
      </template>
    </b-modal>
    <b-modal
      id="modal-all-access"
      ref="modal"
      title="Give access"
    >
      Would you like to give all of your graders access to all of the assignments?
      <template #modal-footer>
        <span v-if="processing">
          <b-spinner small type="grow"/>
          Processing...
        </span>
        <b-button
          size="sm"
          @click="$bvModal.hide('modal-all-access')"
        >
          Cancel
        </b-button>
        <b-button
          size="sm"
          variant="primary"
          class="float-right"
          @click="handleGlobalCourseAccess(1)"
        >
          Yes, give access!
        </b-button>
      </template>
    </b-modal>
    <div class="vld-parent">
      <loading :active.sync="isLoading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"
      />
      <div v-if="!isLoading && user.role === 2">
        <b-card header="default" header-html="Grader Permissions">
          <b-card-text>
            <div v-show="graders.length">
              <b-container>
                <b-row>
                  <p>
                    At the course level, you can <a href="#" @click.prevent="openAllAccessModal()">give all graders
                    access</a> to all assignments or you can
                    <a href="#" @click.prevent="openNoAccessModal()">remove grader access</a> to all assignments.
                  </p>
                </b-row>
              </b-container>
              <b-table striped hover
                       :fields="fields"
                       :items="graderPermissions"
              >
                <template v-slot:cell(assignment_name)="data">
                  <h5>{{ data.item.assignment_name }}</h5>
                  <b-button variant="success" size="sm"
                            @click="handleGlobalAssignmentAccess(data.item.assignment_id, 1)"
                  >
                    Give all access
                  </b-button>
                  <b-button size="sm" @click="handleGlobalAssignmentAccess(data.item.assignment_id, 0)">
                    Remove all access
                  </b-button>
                </template>
                <template v-slot:cell(grader_permissions)="data">
                  <span v-for="grader_permission in data.item.grader_permissions">
                    <toggle-button
                      class="mr-2"
                      :width="60"
                      :value="grader_permission.has_access"
                      :sync="true"
                      :font-size="14"
                      :margin="4"
                      :color="{checked: '#28a745', unchecked: '#6c757d'}"
                      :labels="{checked: 'Yes', unchecked: 'No'}"
                      @change="toggleGraderPermissions(data.item.assignment_id,grader_permission)"
                    />{{ grader_permission.name }}<br>
                  </span>
                </template>
              </b-table>
            </div>
            <div v-show="!graders.length" class="clearfix">
              <b-alert show variant="info">
                <span class="font-weight-bold">You currently have no graders associated with this course.</span>
              </b-alert>
            </div>
          </b-card-text>
        </b-card>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import { ToggleButton } from 'vue-js-toggle-button'
import { mapGetters } from 'vuex'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'

export default {
  middleware: 'auth',
  components: {
    Loading,
    ToggleButton
  },
  data: () => ({
    processing: false,
    fields: [
      {
        key: 'assignment_name',
        label: 'Assignment Name',
        tdClass: 'text-center align-middle'
      },
      {
        key: 'grader_permissions',
        label: 'Can Access'
      }
    ],
    graderPermissions: [],
    graders: [],
    isLoading: true

  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  mounted () {
    this.courseId = this.$route.params.courseId
    this.getCourse(this.courseId)
  },
  methods: {
    openNoAccessModal () {
      this.$bvModal.show('modal-no-access')
    },
    openAllAccessModal () {
      this.$bvModal.show('modal-all-access')
    },
    updateAllAccessByCourse (hasAccess) {
      for (let i = 0; i < this.graderPermissions.length; i++) {
        for (let j = 0; j < this.graderPermissions[i].grader_permissions.length; j++) {
          this.graderPermissions[i].grader_permissions[j].has_access = hasAccess
        }
      }
    },

    updateAllAccessByAssignment (assignmentId, hasAccess) {
      for (let i = 0; i < this.graderPermissions.length; i++) {
        if (this.graderPermissions[i].assignment_id === assignmentId) {
          for (let j = 0; j < this.graderPermissions[i].grader_permissions.length; j++) {
            this.graderPermissions[i].grader_permissions[j].has_access = hasAccess
          }
        }
      }
    },
    async handleGlobalAssignmentAccess (assignmentId, hasAccess) {
      try {
        const { data } = await axios.patch(`/api/grader-permissions/assignment/${assignmentId}/${hasAccess}`)
        this.$noty[data.type](data.message)
        if (data.type !== 'error') {
          this.updateAllAccessByAssignment(assignmentId, hasAccess)
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async handleGlobalCourseAccess (hasAccess) {
      this.processing = true
      try {
        const { data } = await axios.patch(`/api/grader-permissions/course/${this.courseId}/${hasAccess}`)
        this.$noty[data.type](data.message)
        if (data.type !== 'error') {
          this.updateAllAccessByCourse(Boolean(hasAccess))
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.$bvModal.hide('modal-all-access')
      this.$bvModal.hide('modal-no-access')
      this.processing = false
    },
    async toggleGraderPermissions (assignmentId, graderPermission) {
      try {
        const { data } = await axios.patch(`/api/grader-permissions/${assignmentId}/${graderPermission.user_id}/${+graderPermission.has_access}`)
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          return false
        }
        graderPermission.has_access = !graderPermission.has_access
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async getCourse (courseId) {
      try {
        const { data } = await axios.get(`/api/grader-permissions/${courseId}`)
        this.isLoading = false
        if (data.type !== 'success') {
          this.$noty.error(data.message)
          return false
        }
        this.graderPermissions = data.grader_permissions
        this.graders = data.graders
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.isLoading = false
    }
  }
}
</script>

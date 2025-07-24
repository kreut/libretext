<template>
  <div>
    <AllFormErrors :all-form-errors="allFormErrors" :modal-id="'modal-form-errors-invite-co-instructors'"/>
    <b-modal id="modal-new-main-instructor"
             title="New Main Instructor"
             @hidden="roleAfterTransfer === 'become a co-instructor'? $router.push(`/instructors/courses/${courseId}/properties`) : $router.push('/instructors/courses')"
    >
      <p><strong>{{ newMainInstructor.name }}</strong> is now the main instructor for this course.</p>
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide('modal-new-main-instructor')"
        >
          OK
        </b-button>
      </template>
    </b-modal>
    <b-modal
      id="modal-confirm-change-main-instructor"
      ref="modal"
      title="Confirm Change Main Instructor"
    >
      <p>You are about to make <strong>{{ newMainInstructor.name }}</strong> the main instructor for this course.</p>
      <b-form-radio-group
        id="role-after-transfer"
        v-model="roleAfterTransfer"
        class="mt-2"
        stacked
      ><label>Role after transfer:</label>
        <b-form-radio value="become a co-instructor">
          Become a co-instructor
        </b-form-radio>
        <b-form-radio value="leave the course">
          Leave the course
        </b-form-radio>
      </b-form-radio-group>
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide('modal-confirm-change-main-instructor')"
        >
          Cancel
        </b-button>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="changeMainInstructor"
        >
          Update
        </b-button>
      </template>
    </b-modal>
    <b-modal
      id="modal-confirm-remove"
      ref="modal"
      title="Remove Co-Instructor"
    >
      <p>
        Are you sure you would like to remove {{ coInstructorToRemoveName }} as a co-instructor? Once removed, they will
        no longer have access to the
        course
        unless you invite them back.
      </p>
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="cancelRemoveCoInstructor"
        >
          Cancel
        </b-button>
        <b-button
          variant="danger"
          size="sm"
          class="float-right"
          @click="submitRemoveCoInstructor"
        >
          Yes, remove this co-instructor!
        </b-button>
      </template>
    </b-modal>
    <b-modal
      id="modal-invite-co-instructor"
      ref="modal"
      title="Invite Co-Instructor"
    >
      <RequiredText/>
      <b-form ref="form">
        <b-form-group
          label-cols-sm="3"
          label-cols-lg="2"
          label="Email*"
          label-for="co_instructor_email"
        >
          <b-form-input
            id="co_instructor_email"
            v-model="coInstructorForm.email"
            required
            placeholder="Email Address"
            type="text"
            :class="{ 'is-invalid': coInstructorForm.errors.has('email') }"
            @keydown="coInstructorForm.errors.clear('email')"
          />
          <has-error :form="coInstructorForm" field="email"/>
        </b-form-group>
      </b-form>
      <template #modal-footer>
        <span v-if="sendingEmail">
          <b-spinner small type="grow"/>
          Sending Email..
        </span>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="submitInviteCoInstructor(coInstructorForm.email)"
        >
          Submit
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
        <b-card header="default" header-html="<h2 class=&quot;h7&quot;>Co-Instructors</h2>">
          <b-card-text>
            <div v-if="user.email !== 'commons@libretexts.org'">
              <b-container>
                <b-row>
                  <b-button class="mb-2" variant="primary" size="sm" @click="initInviteCoInstructor()">
                    Invite Co-Instructor
                  </b-button>
                </b-row>
              </b-container>
              <div v-if="course.co_instructors.length">
                <b-table striped hover
                         aria-label="Co-Instructors"
                         :fields="fields"
                         :items="coInstructors"
                >
                  <template v-slot:cell(actions)="data">
                    <a
                      v-if="data.item.status === 'pending'"
                      href=""
                      aria-label="Resend Invitation"
                      @click.prevent="submitInviteCoInstructor(data.item.email)"
                    >
                      <span v-b-tooltip.hover
                            :title="`Resend invitation to ${data.item.name}`"
                      >

                        <b-icon icon="envelope"
                                class="text-muted"
                                :aria-label="`Resend invitation to ${data.item.name}`"
                        />
                      </span>
                    </a>
                    <a
                      href=""
                      aria-label="Remove co-instructor"
                      @click.prevent="initRemoveCoInstructor(data.item)"
                    >
                      <span v-b-tooltip.hover
                            :title="data.item.status === 'pending'
                            ? `Revoke co-instructor invitation for ${data.item.name}.`
                             : `Remove ${data.item.name} as a co-instructor`"
                      >

                        <b-icon icon="trash"
                                class="text-muted"
                                :aria-label="data.item.status === 'pending'
                                 ? `Revoke co-instructor invitation for ${data.item.name}.`
                                  : `Remove ${data.item.name} as a co-instructor`"
                        />
                      </span>
                    </a>
                    <a
                      href=""
                      :aria-label="`Make ${data.item.name} the main instructor for this course.`"
                      @click.prevent="initMakeMainInstructor(data.item)"
                    >
                      <span v-b-tooltip.hover
                            v-show="data.item.status === 'accepted'"
                            :title="`Make ${data.item.name} the main instructor for this course.`"
                      >
                      <font-awesome-icon :icon="transferIcon"
                                         class="text-muted"
                                         :aria-label="`Make ${data.item.name} the main instructor for this course.`"
                      />
                      </span>
                    </a>
                  </template>
                </b-table>
              </div>
              <div v-show="!course.co_instructors.length">
                <b-alert show variant="info">
                  <span class="font-weight-bold"
                  >You currently have no co-instructors associated with this course.</span>
                </b-alert>
              </div>
            </div>
            <div v-else>
              <b-alert :show="true" variant="info">
                <span class="font-weight-bold">You cannot invite co-instructors to courses in the Commons.</span>
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
import Form from 'vform'
import { fixInvalid } from '~/helpers/accessibility/FixInvalid'
import { mapGetters } from 'vuex'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import { faPeopleArrows } from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import AllFormErrors from '~/components/AllFormErrors'

export default {
  middleware: 'auth',
  components: {
    Loading,
    AllFormErrors,
    FontAwesomeIcon
  },
  metaInfo () {
    return { title: 'Course Co-Instructors' }
  },
  data: () => ({
    roleAfterTransfer: 'become a co-instructor',
    courseId: 0,
    newMainInstructor: {},
    transferIcon: faPeopleArrows,
    coInstructorToRemoveName: '',
    allFormErrors: [],
    coInstructorOptions: [],
    coInstructorToRemoveId: 0,
    sectionOptions: [],
    fields: [
      {
        key: 'name',
        isRowHeader: true
      },
      'email',
      {
        key: 'status',
        formatter: value => {
          return value.charAt(0).toUpperCase() + value.slice(1)
        }
      },
      'actions'
    ],
    sendingEmail: false,
    isLoading: true,
    coInstructors: {},
    course: { co_instructors: {} },
    coInstructorForm: new Form({
      email: '',
      course_id: 0
    })
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  async mounted () {
    this.courseId = this.$route.params.courseId
    await this.getCourse(this.courseId)
  },
  methods: {
    async changeMainInstructor () {
      try {
        const { data } = await axios.patch(`/api/courses/${this.courseId}/change-main-instructor/${this.newMainInstructor.id}`,
          { role_after_transfer: this.roleAfterTransfer })
        data.type === 'success'
          ? this.$bvModal.show('modal-new-main-instructor')
          : this.$noty.error(data.message)
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    cancelRemoveCoInstructor () {
      this.$bvModal.hide('modal-confirm-remove')
    },
    initMakeMainInstructor (newMainInstructor) {
      this.newMainInstructor = newMainInstructor
      this.$bvModal.show('modal-confirm-change-main-instructor')
    },
    initInviteCoInstructor () {
      this.coInstructorForm.email = ''
      this.coInstructorForm.errors.clear()
      this.$bvModal.show('modal-invite-co-instructor')
    },
    async getCourse (courseId) {
      try {
        const { data } = await axios.get(`/api/courses/${courseId}`)
        this.course = data.course
        this.coInstructors = this.course.co_instructors
        for (let i = 0; i < this.coInstructors.length; i++) {
          let coInstructor = this.coInstructors[i]
          let coInstructorInfo = { text: coInstructor.name, value: coInstructor.user_id }
          this.coInstructorOptions.push(coInstructorInfo)
        }
        this.isLoading = false
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    initRemoveCoInstructor (coInstructor) {
      this.$bvModal.show('modal-confirm-remove')
      this.coInstructorToRemoveId = coInstructor.id
      this.coInstructorToRemoveName = coInstructor.name
    },
    async submitRemoveCoInstructor () {
      try {
        const { data } = await axios.delete(`/api/co-instructors/course/${this.courseId}/co-instructor/${this.coInstructorToRemoveId}`)
        this.$noty[data.type](data.message)
        this.$bvModal.hide('modal-confirm-remove')
        if (data.type === 'error') {
          return false
        }
        await this.getCourse(this.courseId)
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async submitInviteCoInstructor (coInstructorEmail) {
      this.coInstructorForm.email = coInstructorEmail

      if (this.sendingEmail) {
        this.$noty.info('Please be patient while we send the email.')
        return false
      }

      try {
        this.sendingEmail = true
        this.coInstructorForm.course_id = this.courseId
        const { data } = await this.coInstructorForm.post('/api/invitations/co-instructor')
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          this.$bvModal.hide('modal-invite-co-instructor')
        }
      } catch (error) {
        this.sendingEmail = false
        await this.getCourse(this.courseId)
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
          return false
        } else {
          this.allFormErrors = this.coInstructorForm.errors.flatten()
          this.$bvModal.show('modal-form-errors-invite-co-instructors')
          this.$nextTick(() => {
            fixInvalid()
          })
        }
      }
      await this.getCourse(this.courseId)
      this.sendingEmail = false
    }
  }
}
</script>

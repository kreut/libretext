<template>
  <div>
    <AllFormErrors :all-form-errors="allFormErrors" modal-id="modal-form-errors-add-tester"/>
    <div class="vld-parent">
      <b-modal id="modal-init-remove-tester"
               :title="`Confirm Remove ${testerToRemoveName}`"
      >
        <b-form-radio-group id="remove-tester-options"
                            v-model="removeTesterOption"
                            class="mt-2"
                            @change="showRemoveTesterError = false"
        >
          <b-form-radio name="remove-tester-options" value="remove-associated-students">
            Remove the tester and all associated students
            <QuestionCircleTooltip
              id="remove-associated-students-tooltip"
            />
            <b-tooltip target="remove-associated-students-tooltip"
                       delay="250"
                       triggers="hover focus"
            >
            In addition to removing the tester from this course, all associated students, including their submissions and scores,
              will be removed from ADAPT.
            </b-tooltip>
          </b-form-radio>
          <b-form-radio name="remove-tester-options" value="maintain-student-information">
            Remove the tester but maintain the student information
            <QuestionCircleTooltip
              id="maintain-student-information-tooltip"
            />
            <b-tooltip target="maintain-student-information-tooltip"
                       delay="250"
                       triggers="hover focus"
            >
              Although the tester will no longer be able to create new students for this course, the students' information
              will remain in your gradebook.
            </b-tooltip>
          </b-form-radio>
        </b-form-radio-group>
        <ErrorMessage v-if="showRemoveTesterError" message="Please choose an option."/>
        <template #modal-footer>
          <b-button
            size="sm"
            class="float-right"
            @click="$bvModal.hide('remove-tester-options')"
          >
            Cancel
          </b-button>
          <b-button
            variant="primary"
            size="sm"
            class="float-right"
            @click="deleteTester"
          >
            Submit
          </b-button>
        </template>
      </b-modal>
      <loading :active.sync="isLoading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"
      />
      <div v-if="!isLoading && user.role === 2">
        <b-card header="default" header-html="<h2 class=&quot;h7&quot;>Testers</h2>">
          <b-card-text>
            <b-form-group
              label-for="testers"
              label-cols="1"
              label-align="right"
              label="Tester"
            >
              <b-input-group style="width:400px">
                <b-form-input
                  v-model="form.email"
                  placeholder="Email address"
                  :class="{ 'is-invalid': form.errors.has('email') }"
                  @keydown="form.errors.clear('email')"
                />
                <b-input-group-append>
                  <b-button size="sm" variant="primary" @click="addTester()">
                    Add Tester
                  </b-button>
                </b-input-group-append>
                <has-error :form="form" field="email"/>
              </b-input-group>

            </b-form-group>
            <b-table
              v-if="testers.length"
              striped
              hover
              :no-border-collapse="true"
              :items="testers"
              :fields="fields"
            >
              <template v-slot:cell(actions)="data">
                <a href="" @click.prevent="initRemoveTester(data.item.user_id)">
                  <b-icon-trash class="text-muted" :aria-label="`Remove ${data.item.name} as a Tester`"/>
                </a>
              </template>
            </b-table>
            <b-alert :show="!testers.length" variant="info">
              You currently have no testers.
            </b-alert>
          </b-card-text>

        </b-card>
      </div>
    </div>
  </div>
</template>

<script>
import Form from 'vform'
import axios from 'axios'
import { mapGetters } from 'vuex'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import { fixInvalid } from '~/helpers/accessibility/FixInvalid'
import AllFormErrors from '~/components/AllFormErrors'
import ErrorMessage from '~/components/ErrorMessage'

export default {
  components: {
    ErrorMessage,
    Loading,
    AllFormErrors
  },
  data: () => ({
    showRemoveTesterError: false,
    testerToRemoveName: '',
    removeTesterOption: '',
    testerToRemoveId: 0,
    fields: [
      {
        key: 'name',
        sortable: true
      }, {
        key: 'email',
        sortable: true
      },
      'actions'
    ],
    allFormErrors: [],
    isLoading: true,
    testers: [],
    courseId: 0,
    form: new Form({
      email: '',
      courseId: 0
    })
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  mounted () {
    this.courseId = this.$route.params.courseId
    this.getTesters()
  },
  methods: {
    initRemoveTester (testerId) {
      this.showRemoveTesterError = false
      this.testerToRemoveId = testerId
      this.testerToRemoveName = this.testers.find(tester => tester.user_id === testerId).name
      this.$bvModal.show('modal-init-remove-tester')
    },
    async deleteTester () {
      if (!this.removeTesterOption) {
        this.showRemoveTesterError = true
        return false
      }
      try {
        const { data } = await axios.delete(`/api/tester/course/${this.courseId}/user/${this.testerToRemoveId}/${this.removeTesterOption}`)
        this.$noty[data.type](data.message)
        this.$bvModal.hide('modal-init-remove-tester')
        if (data.type === 'error') {
          return false
        }
        await this.getTesters()
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.$bvModal.hide('modal-init-remove-tester')
    },
    async getTesters () {
      try {
        const { data } = await axios.get(`/api/tester/${this.courseId}`)
        this.isLoading = false
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.testers = data.testers
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.isLoading = false
    },
    async addTester () {
      try {
        this.form.course_id = this.courseId
        const { data } = await this.form.post('/api/tester')
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          return false
        }
        this.form.email = ''
        await this.getTesters()
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        } else {
          this.$nextTick(() => fixInvalid())
          this.allFormErrors = this.form.errors.flatten()
          this.$bvModal.show('modal-form-errors-add-tester')
        }
      }
    }

  }
}
</script>


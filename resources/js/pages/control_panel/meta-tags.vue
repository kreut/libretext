<template>
  <div>
    <b-modal id="modal-view-question"
             :title="questionToView.title"
             size="lg"
             hide-footer
    >
      <ViewQuestions :key="`question-to-view-${questionToView.id}`" :question-ids-to-view="[questionToView.id]"/>
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
      <div v-if="!isLoading">
        <PageTitle title="Meta-Tags"/>
        <b-modal v-if="questionMetaTags.length"
                 id="modal-confirm-update-meta-tags"
                 title="Confirm Update Question Meta-tags"
                 size="lg"
        >
          <ul>
            <li>Course: {{ course.label }}</li>
            <li>
              Assignment: {{ assignmentOptions.find(assignment => assignment.value === assignmentId).text }}
            </li>
            <li v-if="metaTagsForm.tag_to_remove">
              Remove the tag: {{ tagToRemoveOptions.find(tag => tag.value === metaTagsForm.tag_to_remove).text }}.
            </li>
            <li v-if="metaTagsForm.tags_to_add">
              Add the tag<span v-if="metaTagsForm.tags_to_add.search(',')>0">s</span>: {{ metaTagsForm.tags_to_add }}.
            </li>
            <li v-if="metaTagsForm.author">
              Change the author to: {{ metaTagsForm.author }}.
            </li>
            <li v-if="metaTagsForm.license">
              Update the license to: {{
                licenseOptions.find(item => item.value === metaTagsForm.license).text
              }}{{ metaTagsForm.license_version ? ' ' + metaTagsForm.license_version + '.' : '.' }}
            </li>
            <li>
              Apply to: <span v-if="metaTagsForm.apply_to === 'all'">all questions</span>
              <span v-if="metaTagsForm.apply_to !== 'all'">Question ID {{
                  metaTagsForm.apply_to
                }}  --- {{ getQuestionTitleByID(metaTagsForm.apply_to) }}</span>
            </li>
          </ul>
          <template #modal-footer>
            <b-button
              size="sm"
              class="float-right"
              aria-label="Cancel update meta-tags"
              @click="$bvModal.hide('modal-confirm-update-meta-tags')"
            >
              Cancel
            </b-button>
            <b-button
              variant="primary"
              size="sm"
              class="float-right"
              aria-label="Update meta-tags"
              @click="updateMetaTags()"
            >
              Do it!
            </b-button>
          </template>
        </b-modal>
        <b-form-group
          label-cols-sm="3"
          label-cols-lg="2"
          label-for="course"
          label="Course"
        >
          <b-form-row>
            <v-select id="course"
                      v-model="course"
                      placeholder="Choose a course"
                      :options="courseOptions"
                      style="width:685px"
                      @input="getCourseAssignments(course)"
            />
          </b-form-row>
        </b-form-group>
        <b-form-group
          label-cols-sm="3"
          label-cols-lg="2"
          label-for="assignment"
          label="Assignment"
        >
          <b-form-row>
            <b-form-select id="assignment"
                           v-model="assignmentId"
                           :options="assignmentOptions"
                           style="width:685px"
                           @change="getQuestionMetaInfoByCourseAssignment($event)"
            />
          </b-form-row>
        </b-form-group>
        <div v-if="questionMetaTags.length">
          <b-form>
            <b-form-group
              label-cols-sm="3"
              label-cols-lg="2"
              label-for="remove_tag"
              label="Tag to remove"
            >
              <b-form-select v-if="tagToRemoveOptions.length > 1"
                             id="remove_tag"
                             v-model="metaTagsForm.tag_to_remove"
                             cols="5"
                             required
                             size="sm"
                             class="mr-2"
                             :options="tagToRemoveOptions"
              />
              <div v-if="tagToRemoveOptions.length === 1" class="pt-2">
                There are no tags which you can remove.
              </div>
            </b-form-group>
            <b-form-group
              label-cols-sm="3"
              label-cols-lg="2"
              label-for="tags_to_add"
            >
              <template v-slot:label>
                Tag(s) to add
                <QuestionCircleTooltip id="tags-to-add-tooltip"/>
                <b-tooltip target="tags-to-add-tooltip"
                           delay="250"
                           triggers="hover focus"
                >
                 A comma-separated list of tags to add.
                </b-tooltip>
              </template>

              <b-form-input
                id="tags_to_add"
                v-model="metaTagsForm.tags_to_add"
                type="text"
                placeholder=""
              />
            </b-form-group>
            <b-form-group
              label-cols-sm="3"
              label-cols-lg="2"
              label-for="author"
              label="Author"
            >
              <b-form-input
                id="tags_to_add"
                v-model="metaTagsForm.author"
                type="text"
                placeholder=""
              />
            </b-form-group>

            <b-form-group
              label-cols-sm="3"
              label-cols-lg="2"
              label-for="license"
              label="License"
            >
              <b-form-row>
                <b-form-select v-model="metaTagsForm.license"
                               style="width:200px"
                               title="license"
                               size="sm"
                               class="mt-2  mr-2"
                               :options="licenseOptions"
                               @change="metaTagsForm.license_version = updateLicenseVersions(metaTagsForm.license)"
                />
              </b-form-row>
            </b-form-group>
            <b-form-group
              v-if="licenseVersionOptions.length"
              label-cols-sm="3"
              label-cols-lg="2"
              label-for="license_version"
              label="License Version"
            >
              <b-form-row>
                <b-form-select v-model="metaTagsForm.license_version"
                               style="width:100px"
                               title="license version"
                               required
                               size="sm"
                               class="mt-2"
                               :options="licenseVersionOptions"
                />
              </b-form-row>
            </b-form-group>
            <b-form-group
              label-cols-sm="3"
              label-cols-lg="2"
              label-for="apply_to"
              label="Apply to"
            >
              <b-form-row>
                <b-form-input
                  id="apply_to"
                  v-model="metaTagsForm.apply_to"
                  type="text"
                  placeholder="Question ID or all"
                />
              </b-form-row>
            </b-form-group>
          </b-form>
          <div class="mt-2 mb-2">
            <b-button variant="primary"
                      size="sm"
                      @click="confirmUpdateMetaTags()"
            >
              Update meta-tags
            </b-button>
          </div>
          <b-row>
            <b-col sm="5" md="6" class="my-1">
              <b-form-group
                label="Per page"
                label-for="per-page-select"
                label-cols-sm="6"
                label-cols-md="4"
                label-cols-lg="3"
                label-align-sm="right"
                label-size="sm"
                class="mb-0"
              >
                <b-form-select
                  id="per-page-select"
                  v-model="perPage"
                  style="width:100px"
                  :options="pageOptions"
                  size="sm"
                  @change="getQuestionMetaInfoByCourseAssignment(assignmentId)"
                />
              </b-form-group>
            </b-col>
            <b-col sm="7" md="6" class="my-1">
              <b-pagination
                v-model="currentPage"
                :total-rows="totalNumQuestions"
                :per-page="perPage"
                align="center"
                first-number
                last-number
                class="my-0"
                @input="getQuestionMetaInfoByCourseAssignment(assignmentId)"
              />
            </b-col>
          </b-row>
          <b-table
            aria-label="Question tags"
            striped
            hover
            responsive
            :no-border-collapse="true"
            :items="questionMetaTags"
            :fields="fields"
          >
            <template v-slot:cell(title)="data">
              <a href="" @click.prevent="initViewQuestion(data.item)">
                {{ data.item.title }}
              </a>
            </template>
            <template v-slot:cell(id)="data">
              <span id="questionID">{{ data.item.id }}</span>
              <a
                href=""
                class="pr-1 text-muted"
                aria-label="Copy question Id"
                @click.prevent="doCopy('questionID')"
              >
                <font-awesome-icon :icon="copyIcon" />
              </a>
            </template>
            <template v-slot:cell(tags)="data">
              {{ data.item.tags.length ? data.item.tags.join(', ') : 'None' }}
            </template>
            <template v-slot:cell(license)="data">
              {{
                data.item.license ? licenseOptions.find(licenseOption => licenseOption.value === data.item.license).text : 'None specified.'
              }}
            </template>
            <template v-slot:cell(license_version)="data">
              {{ data.item.license_version ? data.item.license_version : 'N/A' }}
            </template>
          </b-table>
        </div>
        <div v-if="!gettingQuestions && !questionMetaTags.length && courseId && assignmentId">
          <b-alert variant="info" show>
            This assignment has no questions.
          </b-alert>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Loading from 'vue-loading-overlay'
import Form from 'vform/src'
import 'vue-loading-overlay/dist/vue-loading.css'
import axios from 'axios'
import { defaultLicenseVersionOptions, licenseOptions, updateLicenseVersions } from '~/helpers/Licenses'
import 'vue-select/dist/vue-select.css'
import { doCopy } from '~/helpers/Copy'
import { faCopy } from '@fortawesome/free-regular-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import ViewQuestions from '~/components/ViewQuestions'

let defaultMetaTagsForm = {
  tag_to_remove: null,
  tags_to_add: '',
  author: '',
  license: null,
  license_version: null,
  apply_to: ''
}
export default {
  components: {
    ViewQuestions,
    Loading,
    FontAwesomeIcon
  },
  data: () => ({
    gettingQuestions: true,
    questionToView: 0,
    copyIcon: faCopy,
    totalNumQuestions: 0,
    currentPage: 1,
    pageOptions: [10, 20, 50, { value: 10000, text: 'Show All' }],
    perPage: 10,
    metaTagsForm: new Form(defaultMetaTagsForm),
    licenseOptions: licenseOptions,
    defaultLicenseVersionOptions: defaultLicenseVersionOptions,
    licenseVersionOptions: [],
    tagToRemoveOptions: [],
    courseId: null,
    course: [],
    courseOptions: [{
      value: null,
      text: 'Choose a course'
    }
    ],
    assignmentId: null,
    assignmentOptions: [{
      value: null,
      text: 'Choose an assignment'
    }],
    isLoading: true,
    questionMetaTags: [],
    fields: [
      {
        key: 'id',
        label: 'Question ID',
        isRowHeader: true
      },
      'title',
      'author',
      'license',
      'license_version',
      'tags'
    ]
  }),
  mounted () {
    this.doCopy = doCopy
    this.getAllCourses()
    this.updateLicenseVersions = updateLicenseVersions
  },
  methods: {
    initViewQuestion (questionToView) {
      this.questionToView = questionToView

      this.$bvModal.show('modal-view-question')
    },
    async updateMetaTags () {
      try {
        const { data } = await this.metaTagsForm.patch(`/api/meta-tags/course/${this.courseId}/assignment/${this.assignmentId}`)
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          this.metaTagsForm = new Form(defaultMetaTagsForm)
          this.$bvModal.hide('modal-confirm-update-meta-tags')
          await this.getQuestionMetaInfoByCourseAssignment(this.assignmentId)
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    getQuestionTitleByID (id) {
      console.log(id)
      let question = this.questionMetaTags.find(question => parseInt(question.id) === parseInt(id))
      return question
        ? question.title
        : ''
    },
    confirmUpdateMetaTags () {
      if (!this.metaTagsForm.apply_to) {
        this.$noty.info('Please select a question or choose "all".')
        return false
      }
      if (this.metaTagsForm.apply_to !== 'all' && !this.questionMetaTags.find(question => parseInt(question.id) === parseInt(this.metaTagsForm.apply_to))) {
        this.$noty.info(`The question with ID ${this.metaTagsForm.apply_to} is not a part of this query.`)
        return false
      }
      this.metaTagsForm.apply_to && (this.metaTagsForm.tags_to_add ||
        this.metaTagsForm.tag_to_remove ||
        this.metaTagsForm.author ||
        this.metaTagsForm.license)
        ? this.$bvModal.show('modal-confirm-update-meta-tags')
        : this.$noty.info('Please first choose something to update.')
    },
    async getCourseAssignments (course) {
      this.courseId = course.value
      if (!this.courseId) {
        this.$noty.info('Please first choose a course')
        return false
      }
      this.questionMetaTags = []
      this.tagToRemoveOptions = []
      this.assignmentOptions = [{
        value: null,
        text: 'Choose an assignment'
      }, {
        value: 'all',
        text: 'All assignments'
      }]
      try {
        const { data } = await axios.get(`/api/assignments/names-ids-by-course/${this.courseId}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          this.assignmentId = null
          this.isLoading = false
          return false
        }

        console.log(data.assignments)
        for (let i = 0; i < data.assignments.length; i++) {
          this.assignmentOptions.push(data.assignments[i])
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.assignmentId = null
    },
    async getAllCourses () {
      try {
        const { data } = await axios.get(`/api/courses/all`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          this.isLoading = false
          return false
        }
        this.courseOptions = data.courses
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.isLoading = false
    },
    async getQuestionMetaInfoByCourseAssignment (assignmentId) {
      this.gettingQuestions = true
      if (!assignmentId) {
        return false
      }
      try {
        const { data } = await axios.get(`/api/meta-tags/course/${this.courseId}/assignment/${assignmentId}/${this.perPage}/${this.currentPage}`)
        console.log(data)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          this.isLoading = false
          return false
        }
        this.questionMetaTags = data.question_meta_tags
        this.tagToRemoveOptions = data.tag_to_remove_options
        this.totalNumQuestions = data.total_num_questions
        this.gettingQuestions = false
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.isLoading = false
    }
  }
}
</script>

<template>
  <div>
    <b-modal id="modal-view-question"
             :title="questionToView.title"
             size="lg"
             hide-footer
    >
      <ViewQuestions :key="`question-to-view-${questionToView.id}`" :question-ids-to-view="[questionToView.id]"/>
    </b-modal>
    <p>
      <span v-if="adminView">Update questions' tags, owner, author, license, and source URL in bulk for any course and assignment.</span>
      <span v-if="!adminView">Update your questions' tags, owner, author, license, and source URL in bulk. After filtering by course and assignment or by a folder in My Questions, you can then update the meta-tags for questions that you own.</span>
    </p>
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
        <b-modal v-if="questionMetaTags.length"
                 id="modal-confirm-update-meta-tags"
                 title="Confirm Update Question Meta-tags"
                 size="lg"
        >
          <ul>
            <li v-if="courseId">
              Course: {{ course.label }}
            </li>
            <li v-if="assignmentId">
              Assignment: {{ assignmentOptions.find(assignment => assignment.value === assignmentId).text }}
            </li>
            <li v-if="myQuestionsFolderId">
              Folder: {{ myQuestionsFoldersOptions.find(folder => folder.value === myQuestionsFolderId).text }}
            </li>
            <li v-if="metaTagsForm.tag_to_remove">
              Remove the tag: {{ getTagToRemove() }}
            </li>
            <li v-if="metaTagsForm.tags_to_add">
              Add the tag<span v-if="metaTagsForm.tags_to_add.search(',')>0">s</span>: {{ metaTagsForm.tags_to_add }}
            </li>
            <li v-if="metaTagsForm.author">
              Change the author to: {{ metaTagsForm.author }}
            </li>
            <li v-if="metaTagsForm.owner">
              Change the owner to:
              {{ metaTagsForm.owner.label }}
            </li>
            <li v-if="metaTagsForm.license">
              Update the license to: {{
                licenseOptions.find(item => item.value === metaTagsForm.license).text
              }}{{ metaTagsForm.license_version ? ' ' + metaTagsForm.license_version + '.' : '.' }}
            </li>
            <li v-if="metaTagsForm.source_url">
              Update the source URL to: {{ metaTagsForm.source_url }}
            </li>
            <li>
              Apply to: <span v-if="metaTagsForm.apply_to === 'all'">all questions</span>
              <span v-if="metaTagsForm.apply_to !== 'all'">Question ID {{
                  metaTagsForm.apply_to
                }}  --- {{ getQuestionTitleByID(metaTagsForm.apply_to) }}</span>
            </li>
          </ul>
          <div v-if="metaTagsForm.owner && (!adminView && !isMe)">
            <b-alert variant="info" show>
              The new owner will receive an email verifying whether they would like ownership of the question(s).
            </b-alert>
          </div>
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
          v-if="!adminView"
          label-cols-sm="3"
          label-cols-lg="2"
          label="Filter By"
          label-for="filter-by"
        >
          <b-form-radio-group
            id="filter-by"
            v-model="filterBy"
            stacked
            @change="updateFilterBy($event)"
          >
            <b-form-radio value="courses_assignments">
              Courses and assignments
            </b-form-radio>
            <b-form-radio value="my_questions_folders">
              My Questions
            </b-form-radio>
          </b-form-radio-group>
        </b-form-group>

        <div v-if="filterBy === 'courses_assignments'">
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
                             @change="currentPage=1;perPage=10;getQuestionMetaInfoByFilter()"
              />
            </b-form-row>
          </b-form-group>
        </div>
        <div v-if="filterBy === 'my_questions_folders'">
          <b-form-group
            label-cols-sm="3"
            label-cols-lg="2"
            label-for="my_questions_folder"
            label="My Questions"
          >
            <b-form-select id="my_questions_folder"
                           v-model="myQuestionsFolderId"
                           style="width:300px"
                           required
                           size="sm"
                           class="mt-2"
                           :options="myQuestionsFoldersOptions"
                           @change="currentPage=1;perPage=10;getQuestionMetaInfoByFilter()"
            />
          </b-form-group>
        </div>
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
                             style="width:300px"
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
              label-for="owner"
            >
              <template v-slot:label>
                Owner
                <QuestionCircleTooltip id="owner-tooltip"/>
                <b-tooltip target="owner-tooltip"
                           delay="250"
                           triggers="hover focus"
                >
                  The ADAPT user who has editing rights.
                </b-tooltip>
              </template>
              <v-select id="owner"
                        v-model="metaTagsForm.owner"
                        placeholder="Choose a new owner"
                        :options="questionEditorOptions"
                        style="width:300px"
              />
            </b-form-group>
            <b-form-group
              label-cols-sm="3"
              label-cols-lg="2"
              label-for="author"
              label="Author"
            >
              <template v-slot:label>
                Author
                <QuestionCircleTooltip id="author-tooltip"/>
                <b-tooltip target="author-tooltip"
                           delay="250"
                           triggers="hover focus"
                >
                  The author for attribution purposes.
                </b-tooltip>
              </template>
              <b-form-input
                id="author"
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
              label-for="source_url"
              label="Source URL"
            >
              <b-form-row>
                <b-form-input
                  id="source_url"
                  v-model="metaTagsForm.source_url"
                  size="sm"
                  type="text"
                  class="mt-2"
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
                  @change="getQuestionMetaInfoByFilter()"
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
                @input="getQuestionMetaInfoByFilter()"
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
                <font-awesome-icon :icon="copyIcon"/>
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
            <template v-slot:cell(source_url)="data">
              <span v-if="data.item.source_url">
                <a :href="data.item.source_url">{{ getDomain(data.item.source_url) }}</a>
              </span>
              <span v-if="!data.item.source_url">
                None provided.
              </span>
            </template>
          </b-table>
        </div>
        <div v-if="!gettingQuestions && !questionMetaTags.length && courseId && assignmentId">
          <b-alert variant="info" show>
            {{ adminView ? 'This assignment has no questions.' : 'This assignment has no questions that you own.' }}
          </b-alert>
        </div>
        <div v-if="!gettingQuestions && !questionMetaTags.length && myQuestionsFolderId">
          <b-alert variant="info" show>
            This folder has no questions.
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
import { mapGetters } from 'vuex'

let defaultMetaTagsForm = {
  filter_by: {},
  tag_to_remove: null,
  tags_to_add: '',
  owner: '',
  author: '',
  license: null,
  license_version: null,
  source_url: '',
  apply_to: ''
}
export default {
  components: {
    ViewQuestions,
    Loading,
    FontAwesomeIcon
  },
  data: () => ({
    myQuestionsFolderId: null,
    myQuestionsFoldersOptions: [
      {
        value: null,
        text: 'Choose a folder'
      },
      {
        value: 'all',
        text: 'All folders'
      }
    ],
    filterBy: 'courses_assignments',
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
    questionEditorOptions: [{
      value: null,
      text: 'Choose an owner'
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
        isRowHeader: true,
        thStyle: { width: '115px' }
      },
      'title',
      'author',
      'license',
      'license_version',
      {
        key: 'source_url',
        label: 'Source URL'
      },
      'tags'
    ],
    adminView: false
  }),
  computed: {
    isMe: () => window.config.isMe,
    ...mapGetters({
      user: 'auth/user'
    })
  },
  mounted () {
    this.adminView = this.$route.path === '/control-panel/classification-manager'
    this.doCopy = doCopy
    this.getAllCourses()
    this.getMyQuestionsFolders()
    this.getAllQuestionEditors()
    this.updateLicenseVersions = updateLicenseVersions
  },
  methods: {
    getTagToRemove () {
      let tagToRemove = this.tagToRemoveOptions.find(tag => tag.value === this.metaTagsForm.tag_to_remove)
      return tagToRemove
        ? tagToRemove.text
        : ''
    },
    async getMyQuestionsFolders () {
      try {
        const { data } = await axios.get('/api/saved-questions-folders/options/my-questions-folders')
        if (data.type === 'error') {
          this.$noty.error(data.error.message)
          return false
        }
        for (let i = 0; i < data.my_questions_folders.length; i++) {
          let myQuestionsFolder = data.my_questions_folders[i]
          this.myQuestionsFoldersOptions.push({
            value: myQuestionsFolder.id,
            text: myQuestionsFolder.name
          })
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    updateFilterBy () {
      this.assignmentId = null
      this.courseId = null
      this.myQuestionsFolderId = null
      this.questionMetaTags = []
    },
    getDomain (url) {
      try {
        let domain = (new URL(url))
        return domain.hostname === url ? url : domain.hostname + '...'
      } catch {
        console.log(url)
        return url
      }
    },
    async getAllQuestionEditors () {
      try {
        const { data } = await axios.get('/api/user/question-editors')
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.questionEditorOptions = data.question_editors
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    initViewQuestion (questionToView) {
      this.questionToView = questionToView

      this.$bvModal.show('modal-view-question')
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
        this.$noty.info('For the "Apply to", please enter a single question ID or the word "all".')
        return false
      }
      if (this.metaTagsForm.apply_to !== 'all' && !this.questionMetaTags.find(question => parseInt(question.id) === parseInt(this.metaTagsForm.apply_to))) {
        this.$noty.info(`The question with ID ${this.metaTagsForm.apply_to} is not a part of this query.`)
        return false
      }
      let somethingUpdated = this.metaTagsForm.apply_to && (this.metaTagsForm.owner ||
        this.metaTagsForm.tags_to_add ||
        this.metaTagsForm.tag_to_remove ||
        this.metaTagsForm.author ||
        this.metaTagsForm.license ||
        this.metaTagsForm.source_url)
      if (!somethingUpdated) {
        this.$noty.info('Please first choose something to update.')
      } else {
        if (this.metaTagsForm.source_url) {
          const withHttps = url => !/^https?:\/\//i.test(url) ? `https://${url}` : url
          this.metaTagsForm.source_url = withHttps(this.metaTagsForm.source_url)
        }
        this.$bvModal.show('modal-confirm-update-meta-tags')
      }
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
        let url = this.adminView ? `/api/courses/all` : '/api/courses'
        const { data } = await axios.get(url)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          this.isLoading = false
          return false
        }
        if (this.adminView) {
          this.courseOptions = data.courses
        } else {
          this.courseOptions = []
          for (let i = 0; i < data.courses.length; i++) {
            let course = data.courses[i]
            let courseInfo = { value: course.id, label: course.name }
            this.courseOptions.push(courseInfo)
          }
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.isLoading = false
    },
    async updateMetaTags () {
      try {
        this.metaTagsForm.filter_by = this.getFilterByParams()
        const { data } = await this.metaTagsForm.patch(`/api/meta-tags`)
        let timeout = data.timeout ? data.timeout : 6000
        this.$noty[data.type](data.message, { timeout: timeout })
        if (data.type === 'success') {
          this.$bvModal.hide('modal-confirm-update-meta-tags')
          await this.getQuestionMetaInfoByFilter()
          this.metaTagsForm = new Form(defaultMetaTagsForm)
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    getFilterByParams () {
      return this.filterBy === 'courses_assignments' ? {
        assignment_id: this.assignmentId,
        course_id: this.courseId
      } : { folder_id: this.myQuestionsFolderId }
    },
    async getQuestionMetaInfoByFilter () {
      this.gettingQuestions = true
      if (!this.assignmentId && !this.myQuestionsFolderId) {
        return false
      }
      try {
        let filterBy = this.getFilterByParams()
        const { data } = await axios.post(`/api/meta-tags/admin-view/${+this.adminView}/${this.perPage}/${this.currentPage}`, filterBy)
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

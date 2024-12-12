<template>
  <div>
    <AllFormErrors :all-form-errors="allFormErrors" modal-id="modal-form-errors-unenroll-student"/>
    <AllFormErrors :all-form-errors="allFormErrors" modal-id="modal-form-errors-move-student"/>
    <AllFormErrors :all-form-errors="allFormErrors" modal-id="modal-form-errors-student-email"/>
    <AllFormErrors :all-form-errors="allFormErrors" modal-id="modal-form-errors-invite-student-error"/>
    <b-modal id="modal-confirm-revoke-all-invitations"
             title="Confirm Revocation of All Invitations"
             no-close-on-backdrop
    >
      <p>
        Confirm that you would like to revoke all invitations to your course. Access codes sent to students
        will be invalidated.
      </p>
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide('modal-confirm-revoke-all-invitations')"
        >
          Cancel
        </b-button>
        <b-button
          size="sm"
          variant="danger"
          class="float-right"
          @click="revokeAllInvitations()"
        >
          Revoke Invitations
        </b-button>
      </template>
    </b-modal>

    <b-modal id="modal-invite-students-by-email"
             title="Invite Students From Email List"
             no-close-on-backdrop
    >
      <table class="table table-striped table-sm">
        <thead>
        <tr>
          <th>Email</th>
          <th>Status</th>
        </tr>
        <tr v-for="(studentToInvite, rowIndex) in emailListInfo" :key="`students-to-invite-by-email-rows-${rowIndex}`">
          <td>{{ studentToInvite.email }}</td>
          <td>
              <span :class="getStudentToInviteStatusClass('Status', studentToInvite.status)">
                {{ studentToInvite.status }}
              </span>
          </td>
        </tr>
        </thead>
      </table>
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="cancelInviteStudentsByEmail"
        >
          Cancel
        </b-button>
        <b-button
          size="sm"
          variant="primary"
          class="float-right"
          @click="$bvModal.hide('modal-invite-students-by-email')"
        >
          OK
        </b-button>
      </template>
    </b-modal>
    <b-modal id="modal-confirm-delete-student-invitation"
             title="Confirm revoke invitation"
    >
      <p>
        Please confirm that you would like to revoke the invitation sent to
        <span class="font-weight-bold">{{
            studentInvitationToBeDeleted.name !== ' ' ? studentInvitationToBeDeleted.name : studentInvitationToBeDeleted.email
          }}</span>.
      </p>
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide('modal-confirm-delete-student-invitation')"
        >
          Cancel
        </b-button>
        <b-button
          variant="danger"
          size="sm"
          class="float-right"
          @click="deleteStudentInvitation"
        >
          Revoke Invitation
        </b-button>
      </template>
    </b-modal>

    <b-modal id="modal-upload-roster"
             title="Upload Roster"
             size="lg"
             no-close-on-backdrop
             @hidden="files = []"
    >
      <file-upload
        :key="fileUploadKey"
        ref="upload"
        v-model="files"
        class="btn btn-success btn-sm"
        accept=".csv"
        put-action="/put.method"
        @input-file="inputFile"
        @input-filter="inputFilter"
      >
        Choose File
      </file-upload>

      <div v-if="files.length && (preSignedURL !== '')">
        <span v-for="file in files" :key="file.id">File to upload:
          <span :class="file.success ? 'text-success font-weight-bold' : ''">{{
              file.name
            }}</span>
          <span v-if="file.error" class="text-danger">Error: {{ file.error }}</span>
          <b-button v-if="!processingFile && (preSignedURL !== '') && (!$refs.upload || !$refs.upload.active)"
                    variant="info"
                    size="sm"
                    style="vertical-align: top"
                    @click.prevent="$refs.upload.active = true"
          >
            Import
          </b-button>
          <span v-else-if="file.active" class="ml-2 text-info">
            <b-spinner small type="grow"/>
            Uploading File...
          </span>
          <span v-if="processingFile && !file.active" :class="processingFileError ? 'text-danger' : 'text-info'">
            <b-spinner
              small
              type="grow"
            />
            {{ processingFileMessage }}
          </span>
        </span>
        <div v-if="studentsToInvite.length" class="pt-4">
          <table class="table table-striped table-sm">
            <thead>
            <tr>
              <th v-for="(header,colIndex) in studentsToInviteHeaders" :key="`students-to-invite-header-${colIndex}`"
                  scope="col"
              >
                {{ header }}
              </th>
            </tr>
            <tr v-for="(studentToInvite, rowIndex) in studentsToInvite" :key="`students-to-invite-rows-${rowIndex}`">
              <td v-for="(header,colIndex) in studentsToInviteHeaders" :key="`students-to-invite-columns-${colIndex}`">
                  <span :class="getStudentToInviteStatusClass(header, studentToInvite[header])">
                    {{ studentToInvite[header] }}
                  </span>
              </td>
            </tr>
            </thead>
          </table>
        </div>
      </div>
      <template #modal-footer>
        <div class="float-right">
          <b-button
            size="sm"
            @click="$bvModal.hide('modal-upload-roster')"
          >
            Cancel
          </b-button>
        </div>
        <div class="float-right">
          <b-button
            size="sm"
            variant="primary"
            @click="$bvModal.hide('modal-upload-roster')"
          >
            OK
          </b-button>
        </div>
      </template>
    </b-modal>
    <b-modal id="modal-invite-students"
             :title="`Invite Students to ${course.name}`"
             size="lg"
             @hidden="getEnrolledAndInvitedStudents()"
    >
      <p>
        Invite students to enroll in your course by emailing them an access code. Access codes are valid for 48
        hours.
      </p>
      <b-form-group
        v-show="inviteToSectionOptions.length > 2"
        label-for="section"
        label-cols-sm="2"
        label-cols-lg="1"
        label="Section"
      >
        <div style="margin-top:5px">
          <b-form-select v-model="inviteToSection"
                         :options="inviteToSectionOptions"
                         style="width: 200px"
                         size="sm"
                         @change="sectionErrorMessage = ''"
          />
          <ErrorMessage :message="sectionErrorMessage"/>
        </div>
      </b-form-group>
      <b-form-group
        label-cols-sm="3"
        label-cols-lg="2"
        label="Invitation Mode"
        label-for="invitation-type"
      >
        <b-form-radio-group
          id="invitation-type"
          v-model="invitationType"
          class="mt-2"
          @change="clearSingleStudentCourseInvitationForm()"
        >
          <b-form-radio value="roster">
            CSV Upload
          </b-form-radio>
          <b-form-radio value="email">
            Manual (Bulk)
          </b-form-radio>
          <b-form-radio value="single">
            Manual (Individual)
          </b-form-radio>
        </b-form-radio-group>
      </b-form-group>

      <div v-show="invitationType === 'email'">
        <b-form-group
          label-cols-sm="3"
          label-cols-lg="2"
          label-for="name"
          label-align="center"
        >
          <template v-slot:label>
            Email(s)*
            <QuestionCircleTooltip id="email_list"/>
            <b-tooltip target="email_list"
                       delay="250"
                       triggers="hover focus"
            >
              Provide a comma separated list of emails
            </b-tooltip>
          </template>
          <b-form-row>
            <b-form-textarea
              id="public_description"
              v-model="emailList"
              type="text"
              rows="3"
              :class="{ 'is-invalid': emailListError }"
              @keydown="emailListError = ''"
            />
            <ErrorMessage :message="emailListError"/>
          </b-form-row>
        </b-form-group>
      </div>
      <div v-show="invitationType === 'single'">
        <RequiredText/>
        <b-form-group
          label-cols-sm="3"
          label-cols-lg="2"
          label-for="input-last-name"
          label="Last Name*"
        >
          <b-form-row>
            <b-form-input
              id="input-last-name"
              v-model="singleStudentCourseInvitationForm.last_name"
              required
              :class="{ 'is-invalid': singleStudentCourseInvitationForm.errors.has('last_name')}"
              placeholder="Enter last name"
            />
            <has-error :form="singleStudentCourseInvitationForm" field="last_name"/>
          </b-form-row>
        </b-form-group>

        <!-- First Name Field -->
        <b-form-group
          label-cols-sm="3"
          label-cols-lg="2"
          label-for="input-first-name"
          label="First Name*"
        >
          <b-form-row>
            <b-form-input
              id="input-first-name"
              v-model="singleStudentCourseInvitationForm.first_name"
              required
              :class="{ 'is-invalid': singleStudentCourseInvitationForm.errors.has('first_name')}"
              placeholder="Enter first name"
            />
            <has-error :form="singleStudentCourseInvitationForm" field="first_name"/>
          </b-form-row>
        </b-form-group>

        <b-form-group
          label-cols-sm="3"
          label-cols-lg="2"
          label-for="input-email"
          label="Email*"
        >
          <b-form-row>
            <b-form-input
              id="input-email"
              v-model="singleStudentCourseInvitationForm.email"
              type="email"
              :class="{ 'is-invalid': singleStudentCourseInvitationForm.errors.has('email')}"
              required
              placeholder="Enter email"
            />
            <has-error :form="singleStudentCourseInvitationForm" field="email"/>
          </b-form-row>
        </b-form-group>

        <!-- Student ID Field -->
        <b-form-group
          label-cols-sm="3"
          label-cols-lg="2"
          label-for="input-student-id"
          label="Student ID"
        >
          <b-form-row>
            <b-form-input
              id="input-student-id"
              v-model="singleStudentCourseInvitationForm.student_id"
              required
              placeholder="Enter student ID (optional)"
            />
          </b-form-row>
        </b-form-group>
      </div>
      <div v-show="invitationType === 'roster'">
        <b-row class="border-top pt-2">
          <b-col class="border-right">
            <h6 class="pb-1">
              Possible Roster Column Headings
              <QuestionCircleTooltip id="possible_roster_column_headings"/>
              <b-tooltip target="possible_roster_column_headings"
                         delay="250"
                         triggers="hover focus"
              >
                Select the headings that match the headings in your school's downloadable roster. You should include
                some combination
                of first and last name in addition to email. Student ID is optional.
              </b-tooltip>
            </h6>
            <b-form-checkbox v-model="options['First Name']" @change="toggleSelection('First Name')">
              First Name
            </b-form-checkbox>
            <b-form-checkbox v-model="options['Last Name']" @change="toggleSelection('Last Name')">
              Last Name
            </b-form-checkbox>
            <b-form-checkbox v-model="options['Last Name, First Name']"
                             @change="toggleSelection('Last Name, First Name')"
            >
              Last Name, First Name
            </b-form-checkbox>
            <b-form-checkbox v-model="options['First Name Last Name']"
                             @change="toggleSelection('First Name Last Name')"
            >
              First Name Last Name
            </b-form-checkbox>
            <b-form-checkbox v-model="options['Email']" @change="toggleSelection('Email')">
              Email
            </b-form-checkbox>
            <b-form-checkbox v-model="options['Student ID']" @change="toggleSelection('Student ID')">
              Student ID
            </b-form-checkbox>
          </b-col>
          <b-col>
            <h6 class="pb-1">
              Selected Headings
              <QuestionCircleTooltip id="selected_headings"/>
              <b-tooltip target="selected_headings"
                         delay="250"
                         triggers="hover focus"
              >
                The order of these headings should match the order of the headings in your downloadable school roster.
                You can optionally
                download an empty CSV file with these headings and paste in student information as needed.
              </b-tooltip>
            </h6>
            <div v-if="studentRosterUploadTemplateHeaders.length > 0">
              <draggable
                :list="studentRosterUploadTemplateHeaders"
                class="list-group"
                ghost-class="ghost"
                @start="dragging = true"
                @end="dragging = false"
              >
                <div
                  v-for="option in studentRosterUploadTemplateHeaders"
                  :key="option"
                  class="list-group-item"
                >
                  <b-icon icon="list"/>
                  {{ option }} <span class="ml-2" @click="removeSelection(option)"><b-icon-trash/></span>
                </div>
              </draggable>
            </div>
            <div v-if="!studentRosterUploadTemplateHeaders.length">
              <b-alert show variant="info">
                No headings are currently selected.
              </b-alert>
            </div>
          </b-col>
        </b-row>
      </div>
      <template #modal-footer>
        <div class="float-right">
          <span v-show="invitationType === 'roster'">
            <span v-b-tooltip.hover="{ delay: { show: 500, hide: 0 } }"
                  :title="selectedValidOptions ? 'Download a CSV file with the chosen ordered headings' : 'You need to include first and last names in addition to email before downloading'"
            >
              <b-button
                :disabled="!selectedValidOptions"
                variant="info"
                size="sm"
                @click="downloadRosterTemplate()"
              >
                Download Roster Template
              </b-button>
            </span>
            <b-button
              variant="primary"
              size="sm"
              @click="initUploadRoster()"
            >
              <span v-b-tooltip.hover="{ delay: { show: 500, hide: 0 } }"
                    title="Upload your student roster.  Your CSV file should contain first/last names, emails, and optionally, Student IDs."
              >
                Upload Roster
              </span>
            </b-button>
          </span>
          <span v-show="invitationType === 'single'">
            <b-button
              variant="primary"
              size="sm"
              @click="inviteSingleStudent()"
            >
              Invite Student
            </b-button>
          </span>
          <span v-show="invitationType === 'email'">
            <b-button
              variant="primary"
              size="sm"
              @click="submitEmailList()"
            >
              Invite Students
            </b-button>
          </span>
          <b-button
            size="sm"
            @click="$bvModal.hide('modal-invite-students')"
          >
            Cancel
          </b-button>
        </div>
      </template>
    </b-modal>
    <b-modal id="modal-update-student-email"
             :title="`Update ${studentToUpdateEmail.name}'s Email`"
    >
      <RequiredText :plural="false"/>
      <b-form-group
        label-cols-sm="2"
        label-cols-lg="3"
        label="Current Email"
        label-for="email"
      >
        <div class="pt-2 font-weight-bold">
          {{ studentToUpdateEmail.email }}
        </div>
      </b-form-group>
      <b-form-group
        label-cols-sm="2"
        label-cols-lg="3"
        label="New Email*"
        label-for="email"
      >
        <b-form-input
          id="email"
          v-model="studentEmailForm.email"
          required
          type="text"
          :class="{ 'is-invalid': studentEmailForm.errors.has('email') }"
          @keydown="studentEmailForm.errors.clear('email')"
        />
        <has-error :form="studentEmailForm" field="email"/>
      </b-form-group>
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide('modal-update-student-email')"
        >
          Cancel
        </b-button>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          @click="updateStudentEmail"
        >
          Update
        </b-button>
      </template>
    </b-modal>
    <b-modal
      id="modal-unenroll-student"
      ref="modal"
      title="Unenroll Student"

      size="lg"
    >
      <b-form ref="form">
        <b-alert show variant="danger">
          <span class="font-weight-bold">Warning! All of this student's submissions will be permanently removed.</span>
        </b-alert>
        <p>
          <span>Please confirm that you would like to unenroll <strong>{{
              studentToUnenroll.name
            }}</strong> from
            <strong>{{ studentToUnenroll.section }}</strong>.</span>
        </p>
        <RequiredText :plural="false"/>
        <b-form-group
          label-cols-sm="1"
          label-cols-lg="2"
          label="Confirmation*"
          label-for="Confirmation"
        >
          <b-form-input
            id="confirmation"
            v-model="unenrollStudentForm.confirmation"
            class="col-6"
            required
            placeholder="Please enter the student's full name."
            type="text"
            :class="{ 'is-invalid': unenrollStudentForm.errors.has('confirmation') }"
            @keydown="unenrollStudentForm.errors.clear('confirmation')"
          />
          <has-error :form="unenrollStudentForm" field="confirmation"/>
        </b-form-group>
      </b-form>
      <template #modal-footer>
        <b-button
          size="sm"
          class="float-right"
          @click="cancelUnenrollStudent"
        >
          Cancel
        </b-button>
        <b-button
          variant="danger"
          size="sm"
          class="float-right"
          @click="submitUnenrollStudent"
        >
          Yes, unenroll the student!
        </b-button>
      </template>
    </b-modal>

    <b-modal
      id="modal-move-student"
      ref="modal"
      title="Move Student To New Section"
    >
      <b-alert show variant="info">
        <span class="font-weight-bold">The student's  submissions from the originating section will be removed
          if the associated assignment doesn't exist in the new section.</span>
      </b-alert>
      <p>
        <span>{{ studentToMove.name }} is currently enrolled in {{ studentToMove.section }}.</span>
      </p>
      <RequiredText :plural="false"/>
      <b-form ref="form">
        <b-form-group
          label-cols-sm="5"
          label-cols-lg="4"
          label="Move student"
          label-for="move_student"
        >
          <template slot="label">
            Move Student*
          </template>
          <div class="mb-2 mr-2">
            <b-form-select
              id="move_student"
              v-model="moveStudentForm.section_id"
              required
              :options="studentSectionOptions"
              :class="{ 'is-invalid': moveStudentForm.errors.has('section_id') }"
              @keydown="moveStudentForm.errors.clear('section_id')"
            />
            <has-error :form="moveStudentForm" field="section_id"/>
          </div>
        </b-form-group>
      </b-form>
      <template #modal-footer>
        <span v-if="processingMoveStudent">
          <b-spinner small type="grow"/>
          Processing...
        </span>
        <b-button
          size="sm"
          class="float-right"
          @click="cancelMoveStudent"
        >
          Cancel
        </b-button>
        <b-button
          variant="primary"
          size="sm"
          class="float-right"
          :disabled="processingMoveStudent"
          @click="submitMoveStudent"
        >
          Yes, move the student!
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
        <WhitelistedDomains :course-id="courseId"/>
        <b-card header="default" header-html="<h2 class=&quot;h7&quot;>Students</h2>">
          <b-card-text>
            <b-container class="pb-3">
              <b-row>
                <b-col lg="5" class="my-1">
                  <b-form-group
                    label="Filter"
                    label-for="filter-input"
                    label-cols-sm="2"
                    label-align-sm="right"
                    label-size="sm"
                    class="mb-0"
                  >
                    <b-input-group size="sm">
                      <b-form-input
                        id="filter-input"
                        v-model="filter"
                        type="search"
                        placeholder="Type to Search"
                      />

                      <b-input-group-append>
                        <b-button :disabled="!filter" @click="filter = ''">
                          Clear
                        </b-button>
                      </b-input-group-append>
                    </b-input-group>
                  </b-form-group>
                </b-col>
                <b-col>
                  <div class="float-right">
                    <a v-show="false"
                       id="download-roster"
                       :href="`/api/enrollments/${courseId}/details/1`"
                    >download roster link</a>
                    <b-button
                      size="sm"
                      variant="primary"
                      @click="downloadRoster"
                    >
                      Download Roster
                    </b-button>
                    <b-button v-show="!course.lms"
                              variant="info"
                              size="sm"
                              @click="initInviteStudents()"
                    >
                      Invite Students
                    </b-button>
                    <b-button v-show="!course.lms"
                              variant="danger"
                              size="sm"
                              :disabled="!hasPendingCourseInvitations"
                              @click="$bvModal.show('modal-confirm-revoke-all-invitations')"
                    >
                      Revoke All Invitations
                    </b-button>
                  </div>
                </b-col>
              </b-row>
            </b-container>
            <div v-show="studentStatus === 'enrolled'">
              <div v-if="enrollments.length">
                <b-table striped
                         hover
                         aria-label="Students"
                         :fields="fields"
                         :items="enrollments"
                         responsive
                         sticky-header="800px"
                         :filter="filter"
                         small
                         style="font-size: 90%"
                >
                  <template v-slot:cell(name)="data">
                    <span v-show="data.item.status === 'enrolled'">
                      <a href="#" @click="loginAsStudentInCourse(data.item.id)">{{ data.item.name }}</a>
                    </span>
                    <span v-show="data.item.status === 'invited'">
                      {{
                        data.item.name
                      }}
                    </span>
                  </template>
                  <template v-slot:cell(email)="data">
                    <span :id="`email-${data.item.id}}`">{{ data.item.email }} </span> <a
                    href="#"
                    class="pr-1"
                    :aria-label="`Copy email for ${data.item.name}`"
                    @click="doCopy(`email-${data.item.id}}`)"
                  >
                    <font-awesome-icon
                      class="text-muted"
                      :icon="copyIcon"
                    />
                  </a>
                    <a v-show="data.item.enrolled"
                       href=""
                       class="pr-1"
                       @click.prevent="initUpdateStudentEmail(data.item)"
                    >
                      <b-icon class="text-muted"
                              icon="pencil"
                              :aria-label="`Edit ${data.item.email}`"
                      />
                    </a>
                  </template>
                  <template v-slot:cell(access_code)="data">
                    <span v-show="data.item.status === 'invited'">
                      <span :id="`pending-invitation-access-code-${data.item.id}}`">{{ data.item.access_code }} </span> <a
                      href="#"
                      class="pr-1"
                      :aria-label="`Copy access code for ${data.item.name}`"
                      @click="doCopy(`pending-invitation-access-code-${data.item.id}}`)"
                    >
                      <font-awesome-icon
                        class="text-muted"
                        :icon="copyIcon"
                      />
                    </a>
                      </span>
                    <span v-show="data.item.status === 'enrolled'">
                      N/A
                    </span>
                  </template>
                  <template v-slot:cell(enrollment_date)="data">
                    {{ data.item.status === 'enrolled' ? data.item.enrollment_date : 'N/A' }}
                  </template>
                  <template v-slot:cell(invitation_sent)="data">
                    {{ data.item.status === 'invited' ? data.item.invitation_sent : 'N/A' }}
                  </template>
                  <template v-slot:cell(actions)="data">
                    <span v-show="data.item.status === 'invited'">
                      <b-tooltip :target="getTooltipTarget('deleteInvitation',data.item.id)"
                                 delay="500"
                                 triggers="hover focus"
                      >
                        Revoke invitation for {{ data.item.name !== ' ' ? data.item.name : data.item.email }}
                      </b-tooltip>
                      <a :id="getTooltipTarget('deleteInvitation',data.item.id)"
                         href=""
                         @click.prevent="initDeleteStudentInvitation(data.item)"
                      >
                        <b-icon icon="trash" class="text-muted"
                                :aria-label="`Revoke invitation for ${data.item.name}`"
                        />
                      </a>
                    </span>
                    <span v-if="data.item.status === 'enrolled'">
                      <b-tooltip :target="getTooltipTarget('moveStudent',data.item.id)"
                                 delay="500"
                                 triggers="hover focus"
                      >
                        Move student to a different section
                      </b-tooltip>
                      <a v-show="sectionOptions.length>1"
                         :id="getTooltipTarget('moveStudent',data.item.id)"
                         href=""
                         @click.prevent="initMoveStudent(data.item)"
                      >
                        <b-icon icon="truck"
                                class="text-muted"
                                :aria-label="`Move ${data.item.name} to a different section`"
                        />
                      </a>
                      <b-tooltip :target="getTooltipTarget('unEnrollStudent',data.item.id)"
                                 delay="500"
                                 triggers="hover focus"
                      >
                        Unenroll {{ data.item.name }}
                      </b-tooltip>
                      <a :id="getTooltipTarget('unEnrollStudent',data.item.id)"
                         href=""
                         @click.prevent="initUnenrollStudent(data.item)"
                      >
                        <b-icon icon="trash" class="text-muted" :aria-label="`Unenroll ${data.item.name}`"/>
                      </a>
                    </span>
                  </template>
                </b-table>
              </div>
              <div v-else>
                <b-alert show variant="info">
                  <span class="font-weight-bold">You currently have no students enrolled in this course.</span>
                </b-alert>
              </div>
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
import { mapGetters } from 'vuex'
import { getTooltipTarget, initTooltips } from '~/helpers/Tooptips'
import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import { loginAsStudentInCourse } from '~/helpers/LoginAsStudentInCourse'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faCopy } from '@fortawesome/free-regular-svg-icons'
import { doCopy } from '~/helpers/Copy'
import AllFormErrors from '~/components/AllFormErrors'
import { fixInvalid } from '~/helpers/accessibility/FixInvalid'
import Vue from 'vue'
import ErrorMessage from '~/components/ErrorMessage.vue'
import draggable from 'vuedraggable'
import WhitelistedDomains from '~/components/WhitelistedDomains.vue'

const VueUploadComponent = require('vue-upload-component')
const defaultSingleStudentCourseInvitationForm = {
  course_id: 0,
  section_id: 0,
  last_name: '',
  first_name: '',
  email: '',
  student_id: '',
  invitation_type: ''
}
Vue.component('file-upload', VueUploadComponent)
export default {
  middleware: 'auth',
  components: {
    WhitelistedDomains,
    ErrorMessage,
    Loading,
    FontAwesomeIcon,
    AllFormErrors,
    draggable
  },
  metaInfo () {
    return { title: 'Course Students' }
  },
  data: () => ({
    cancelledInvitedStudentsByEmail: false,
    emailListInfo: [],
    emailList: '',
    emailListError: '',
    fileUploadKey: 0,
    studentInvitationToBeDeleted: {},
    hasPendingCourseInvitations: false,
    pendingCourseInvitationFields: [],
    studentStatus: 'enrolled',
    singleStudentCourseInvitationForm: new Form(defaultSingleStudentCourseInvitationForm),
    dragging: false,
    sectionErrorMessage: '',
    inviteToSectionOptions: [],
    inviteToSection: null,
    studentsToInvite: [],
    studentsToInviteHeaders: [],
    files: [],
    preSignedURL: '',
    processingFile: false,
    processingFileError: false,
    processingFileMessage: '',
    options: {
      'First Name': false,
      'Last Name': false,
      'Last Name, First Name': false,
      'First Name Last Name': false,
      'Email': false,
      'Student ID': false
    },
    studentRosterUploadTemplateHeaders: [],
    invitationType: 'roster',
    studentToUpdateEmail: {},
    filter: null,
    courseId: 0,
    unEnrollAllStudentsKey: 0,
    course: {},
    allFormErrors: [],
    processingMoveStudent: false,
    copyIcon: faCopy,
    studentToUnenroll: {},
    studentEmailForm: new Form({
      email: ''
    }),
    unenrollStudentForm: new Form({
      confirmation: ''
    }),
    moveStudentForm: new Form({
      section_id: 0
    }),
    studentId: 0,
    sectionId: 0,
    studentSectionOptions: [],
    studentToMove: {},
    sectionToMoveTo: 0,
    sectionsOptions: [],
    enrollments: [],
    sectionOptions: [],
    graderFormType: 'addGrader',
    fields: [],
    isLoading: true,
    students: []
  }),
  computed: {
    ...mapGetters({
      user: 'auth/user'
    }),
    selectedValidOptions: function () {
      return this.studentRosterUploadTemplateHeaders.includes('Email') &&
        ((this.studentRosterUploadTemplateHeaders.includes('First Name') && this.studentRosterUploadTemplateHeaders.includes('Last Name')) ||
          this.studentRosterUploadTemplateHeaders.includes('First Name Last Name') || this.studentRosterUploadTemplateHeaders.includes('Last Name, First Name')
        )
    }
  },
  async mounted () {
    initTooltips(this)
    this.courseId = parseInt(this.$route.params.courseId)
    await this.getEnrolledAndInvitedStudents()
    await this.getCourseInfo()
    this.isLoading = false
    if (localStorage.studentRosterUploadTemplateHeaders) {
      this.studentRosterUploadTemplateHeaders = JSON.parse(localStorage.studentRosterUploadTemplateHeaders)
      for (let i = 0; i < this.studentRosterUploadTemplateHeaders.length; i++) {
        this.options[this.studentRosterUploadTemplateHeaders[i]] = true
      }
    }
  },
  methods: {
    getTooltipTarget,
    doCopy,
    loginAsStudentInCourse,
    async revokeAllInvitations () {
      try {
        const { data } = await axios.delete(`/api/users/courses/${this.courseId}/revoke-student-invitations`)
        this.$noty[data.type](data.message)
        if (data.type !== 'error') {
          await this.getEnrolledAndInvitedStudents()
          this.$bvModal.hide('modal-confirm-revoke-all-invitations')
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    cancelInviteStudentsByEmail () {
      this.$bvModal.hide('modal-invite-students-by-email')
      this.cancelledInvitedStudentsByEmail = true
    },
    async submitEmailList () {
      if (!this.validateSection()) {
        return false
      }
      this.cancelledInvitedStudentsByEmail = false
      if (!this.emailList) {
        this.emailListError = 'Please provide at least 1 email.'
        this.allFormErrors = [this.emailListError]
        this.$bvModal.show('modal-form-errors-invite-student-error')
        return false
      }
      const emailListArr = this.emailList.split(',')
      this.emailListInfo = []
      for (let i = 0; i < emailListArr.length; i++) {
        this.emailListInfo.push({
          email: emailListArr[i],
          status: 'Pending'
        })
      }
      this.$bvModal.show('modal-invite-students-by-email')
      for (let i = 0; i < this.emailListInfo.length; i++) {
        if (!this.cancelledInvitedStudentsByEmail) {
          const emailInfo = {
            course_id: this.courseId,
            section_id: this.inviteToSection,
            email: this.emailListInfo[i].email,
            invitation_type: 'email_list'
          }
          try {
            const { data } = await axios.post('/api/users/invite-student', emailInfo)
            this.emailListInfo[i].status = data.type === 'error' ? data.message : 'Invitation Sent'
          } catch (error) {
            this.emailListInfo[i].status = error.message
          }
        }
      }
      this.$noty.info('All emails have been processed.')
    },
    initDeleteStudentInvitation (student) {
      this.studentInvitationToBeDeleted = student
      this.$bvModal.show('modal-confirm-delete-student-invitation')
    },
    async deleteStudentInvitation () {
      try {
        const { data } = await axios.delete(`/api/pending-course-invitations/${this.studentInvitationToBeDeleted.id}`)
        if (data.type === 'info') {
          this.$bvModal.hide('modal-confirm-delete-student-invitation')
        }
        this.$noty[data.type](data.message)
        await this.getEnrolledAndInvitedStudents()
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async inviteSingleStudent () {
      if (!this.validateSection()) {
        return false
      }
      try {
        this.singleStudentCourseInvitationForm.course_id = this.courseId
        this.singleStudentCourseInvitationForm.section_id = this.inviteToSection
        this.singleStudentCourseInvitationForm.invitation_type = 'single_student'
        const { data } = await this.singleStudentCourseInvitationForm.post(`/api/users/invite-student`)
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          this.clearSingleStudentCourseInvitationForm()
        }
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        } else {
          this.allFormErrors = this.singleStudentCourseInvitationForm.errors.flatten()
          this.$bvModal.show('modal-form-errors-invite-student-error')
        }
      }
    },
    clearSingleStudentCourseInvitationForm () {
      this.emailListError = ''
      this.singleStudentCourseInvitationForm = new Form(defaultSingleStudentCourseInvitationForm)
    },
    validateSection () {
      if (!this.inviteToSection) {
        this.sectionErrorMessage = 'Please choose a section.'
        this.allFormErrors = [this.sectionErrorMessage]
        this.$bvModal.show('modal-form-errors-invite-student-error')
        return false
      }
      return true
    },
    initUploadRoster () {
      if (!this.validateSection()) {
        return false
      }
      this.$bvModal.show('modal-upload-roster')
    },
    initInviteStudents () {
      this.emailListInfo = []
      this.emailList = ''
      this.emailListError = ''
      this.inviteToSectionOptions = [{ text: 'Please choose a section', value: 0 }]
      for (let i = 0; i < this.sectionOptions.length; i++) {
        this.inviteToSectionOptions.push(this.sectionOptions[i])
      }
      this.inviteToSection = this.sectionOptions.length > 1 ? 0 : this.sectionOptions[0].value
      this.$bvModal.show('modal-invite-students')
    },
    getStudentToInviteStatusClass (header, status) {
      if (header !== 'Status') {
        return ''
      }
      switch (status) {
        case ('Pending'):
          return ''
        case ('Invitation Sent'):
          return 'text-success'
        default:
          return 'text-danger'
      }
    },
    inputFile (newFile, oldFile) {
      if (newFile && oldFile && !newFile.active && oldFile.active) {
        // Get response data

        if (newFile.xhr) {
          //  Get the response status code
          // console.log('status', newFile.xhr.status)
          if (newFile.xhr.status === 200) {
            if (!this.handledOK) {
              this.handledOK = true
              // console.log(this.handledOK)
              this.handleOK()
            }
          } else {
            this.$noty.error('We were not able to save your file to our server.  Please try again or contact us if the problem persists.')
          }
        } else {
          this.$noty.error('We were not able to save your file to our server.  Please try again or contact us if the problem persists.')
        }
      }
    },
    async inputFilter (newFile, oldFile, prevent) {
      this.processingFileError = ''
      if (newFile && !oldFile) {
        if (parseInt(newFile.size) > 5000000) {
          this.processingFileError = '5 MB max allowed.  Your file is too large.'
          return prevent()
        }

        const validExtension = /\.(csv)$/i.test(newFile.name)
        if (!validExtension) {
          this.processingFileError = 'Your file should be a .csv file.'
          return prevent()
        } else {
          try {
            this.preSignedURL = ''
            let uploadFileData = {
              upload_file_type: 'student-roster',
              file_name: 'student-roster-template.csv'
            }
            const { data } = await axios.post('/api/s3/pre-signed-url', uploadFileData)
            if (data.type === 'error') {
              this.$noty.error(data.message)
              return false
            }
            this.preSignedURL = data.preSignedURL
            newFile.putAction = this.preSignedURL

            this.s3Key = data.s3_key
            this.originalFilename = newFile.name
            this.handledOK = false
          } catch (error) {
            this.$noty.error(error.message)
            return false
          }
        }
      }

      // Create a blob field
      newFile.blob = ''
      let URL = window.URL || window.webkitURL
      if (URL && URL.createObjectURL) {
        newFile.blob = URL.createObjectURL(newFile.file)
      }
    },
    async getStudentsToInvite () {
      const { data } = await axios.patch('/api/users/get-students-to-invite', { s3_key: this.s3Key })
      return data
    },
    async inviteStudentFromRosterUpload (studentToInvite) {
      try {
        studentToInvite.section_id = this.inviteToSection
        studentToInvite.course_id = this.courseId
        studentToInvite.invitation_type = 'student_from_roster_invitation'
        const { data } = await axios.post(`/api/users/invite-student`, studentToInvite)
        return data.message
      } catch (error) {
        return `Error: ${error.message}`
      }
    },
    async handleOK () {
      try {
        const studentsToInviteData = await this.getStudentsToInvite()
        if (studentsToInviteData.type === 'error') {
          this.$noty.error(studentsToInviteData.message)
          return false
        }
        this.studentsToInvite = studentsToInviteData.students_to_invite
        this.studentsToInviteHeaders = studentsToInviteData.headers
        if (!this.studentsToInvite.length) {
          this.$noty.error('Your roster does not contain any students.')
          return
        }
        for (let i = 0; i < this.studentsToInvite.length; i++) {
          const status = await this.inviteStudentFromRosterUpload(this.studentsToInvite[i])
          console.log(status)
          this.studentsToInvite[i].Status = status
          this.$forceUpdate()
        }
        console.log(this.studentsToInvite)
      } catch (error) {
        this.$noty.error(error.message)
      }
      this.processingFile = false
    },
    async downloadRosterTemplate () {
      if (!this.validateSection()) {
        return false
      }
      try {
        const { data } = await axios.post('/api/users/student-roster-upload-template', { student_roster_upload_template_headers: this.studentRosterUploadTemplateHeaders })
        if (data.type === 'error') {
          this.$noty.error(data.message)
        }

        const url = window.URL.createObjectURL(new Blob([data]))

        // Create a temporary <a> element to download the file
        const link = document.createElement('a')
        link.href = url

        // The filename should match what you set in the 'Content-Disposition' header
        link.setAttribute('download', 'roster-upload-students-template.csv')

        // Append to the document and trigger the download
        document.body.appendChild(link)
        link.click()

        link.remove()

        localStorage.studentRosterUploadTemplateHeaders = JSON.stringify(this.studentRosterUploadTemplateHeaders)
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    toggleSelection (option) {
      if (!this.studentRosterUploadTemplateHeaders.find(item => item === option) &&
        option.includes('First') &&
        this.studentRosterUploadTemplateHeaders.find(item => item.includes('First'))) {
        this.$noty.error('You cannot have multiple columns with the First Name.')
        this.$nextTick(() => {
          this.options[option] = false
        })
        return
      }

      if (!this.studentRosterUploadTemplateHeaders.find(item => item === option) &&
        option.includes('Last') &&
        this.studentRosterUploadTemplateHeaders.find(item => item.includes('Last'))) {
        this.$noty.error('You cannot have multiple columns with the Last Name.')
        this.$nextTick(() => {
          this.options[option] = false
        })
        return
      }

      if (!this.studentRosterUploadTemplateHeaders.includes(option)) {
        this.studentRosterUploadTemplateHeaders.push(option)
      } else {
        this.studentRosterUploadTemplateHeaders = this.studentRosterUploadTemplateHeaders.filter(item => item !== option)
      }
      console.log(this.studentRosterUploadTemplateHeaders)
    },
    removeSelection (option) {
      // Remove the option from the studentRosterUploadTemplateHeaders array
      this.studentRosterUploadTemplateHeaders = this.studentRosterUploadTemplateHeaders.filter(item => item !== option)
      // Uncheck the checkbox as well
      this.options[option] = false
    },
    downloadRoster () {
      document.getElementById('download-roster').click()
    },
    initUpdateStudentEmail (student) {
      this.studentEmailForm.errors.clear()
      this.studentEmailForm.email = ''
      this.studentToUpdateEmail = student
      this.$bvModal.show('modal-update-student-email')
    },
    initUnenrollStudent (student) {
      this.studentToUnenroll = student
      console.log(student)
      this.studentId = student.id
      this.sectionId = student.section_id
      this.$bvModal.show('modal-unenroll-student')
    },
    cancelUnenrollStudent () {
      this.$bvModal.hide('modal-unenroll-student')
    },
    async updateStudentEmail () {
      try {
        const { data } = await this.studentEmailForm.patch(`/api/user/student-email/${this.studentToUpdateEmail.id}`)
        this.$noty[data.type](data.message)
        if (data.type !== 'error') {
          this.$bvModal.hide('modal-update-student-email')
          this.enrollments.find(student => student.id === this.studentToUpdateEmail.id).email = this.studentEmailForm.email
        }
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
          return false
        } else {
          fixInvalid()
          this.allFormErrors = this.studentEmailForm.errors.flatten()
          this.$bvModal.show('modal-form-errors-student-email')
        }
      }
    },
    async submitUnenrollStudent () {
      try {
        const { data } = await this.unenrollStudentForm.delete(`/api/enrollments/${this.sectionId}/${this.studentId}`)
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          await this.getEnrolledAndInvitedStudents()
        }
        this.$bvModal.hide('modal-unenroll-student')
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
          return false
        } else {
          fixInvalid()
          this.allFormErrors = this.unenrollStudentForm.errors.flatten()
          this.$bvModal.show('modal-form-errors-unenroll-student')
        }
      }
      this.unenrollStudentForm.confirmation = ''
    },
    cancelMoveStudent () {
      this.$bvModal.hide('modal-move-student')
    },
    async submitMoveStudent () {
      this.processingMoveStudent = true
      try {
        const { data } = await this.moveStudentForm.patch(`/api/enrollments/${this.courseId}/${this.studentId}`)
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          await this.getEnrolledAndInvitedStudents()
        }
        this.$bvModal.hide('modal-move-student')
        this.processingMoveStudent = false
      } catch (error) {
        this.processingMoveStudent = false
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
          return false
        } else {
          this.$nextTick(() => fixInvalid())
          this.allFormErrors = this.moveStudentForm.errors.flatten()
          this.$bvModal.show('modal-form-errors-move-student')
        }
      }
    },
    initMoveStudent (student) {
      this.moveStudentForm.section_id = 0
      this.studentToMove = student
      this.studentId = student.id
      this.studentSectionOptions = [{ text: 'Please choose a section', value: 0 }]
      for (let i = 0; i < this.sectionOptions.length; i++) {
        let section = this.sectionOptions[i]
        if (section.value !== student.section_id) {
          this.studentSectionOptions.push(section)
        }
      }
      this.$bvModal.show('modal-move-student')
    },
    async getCourseInfo () {
      try {
        const { data } = await axios.get(`/api/courses/${this.courseId}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.course = data.course
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async getPendingCourseInvitations () {
      try {
        const { data } = await axios.get(`/api/pending-course-invitations/${this.courseId}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.hasPendingCourseInvitations = data.pending_course_invitations.length >= 1
        return data.pending_course_invitations
      } catch (error) {
        this.$noty.error(error.message)
        return false
      }
    },
    async getEnrolledAndInvitedStudents () {
      let pendingCourseInvitations
      pendingCourseInvitations = await this.getPendingCourseInvitations()

      if (pendingCourseInvitations === false) {
        return
      }
      try {
        const { data } = await axios.get(`/api/enrollments/${this.courseId}/details`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.enrollments = data.enrollments
        for (let i = 0; i < this.enrollments.length; i++) {
          this.enrollments[i].status = 'enrolled'
        }
        for (let i = 0; i < pendingCourseInvitations.length; i++) {
          let pendingCourseInvitation = pendingCourseInvitations[i]
          pendingCourseInvitation.status = 'invited'
          if (pendingCourseInvitation.name === ' ') {
            pendingCourseInvitation.name = 'None provided'
          }
          this.enrollments.push(pendingCourseInvitation)
        }
        this.sectionOptions = data.sections
        if (this.enrollments.length) {
          this.enrollments.sort((a, b) => {
            if (a.name.toLowerCase() < b.name.toLowerCase()) return -1
            if (a.name.toLowerCase() > b.name.toLowerCase()) return 1
            return 0
          })
        }
        this.fields = [
          {
            key: 'name',
            isRowHeader: true
          },
          'email',
          'enrollment_date',
          'invitation_sent',
          {
            key: 'section',
            label: 'Section'
          },
          'actions']
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }
}
</script>

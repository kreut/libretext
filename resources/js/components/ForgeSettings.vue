<template>
  <div>
    <AllFormErrors :all-form-errors="allFormErrors" :modal-id="'modal-form-errors-forge-settings'"/>

    <!-- Delete Draft Confirmation Modal -->
    <b-modal
      id="modal-confirm-delete-draft"
      title="Confirm Delete Draft"
      @ok="confirmDeleteDraft"
    >
      <p v-if="draftToDelete">
        Are you sure you want to delete "{{ draftToDelete.title || 'Draft ' + (draftToDeleteIndex + 1) }}"?
      </p>
      <b-alert :show="draftSubmissionCount > 0" variant="danger">
        This draft has {{ draftSubmissionCount }} submission{{ draftSubmissionCount !== 1 ? 's' : '' }}.
        Deleting this draft will remove it from the assignment.
      </b-alert>
      <template #modal-footer="{ cancel, ok }">
        <b-button size="sm" @click="cancel()">
          Cancel
        </b-button>
        <b-button size="sm" variant="danger" :disabled="isDeleting" @click="ok()">
          <b-spinner v-if="isDeleting" small class="mr-1"/>
          Delete Draft
        </b-button>
      </template>
    </b-modal>

    <!-- Re-lock Final Submission Confirmation Modal -->
    <b-modal
      id="modal-confirm-relock"
      title="Lock Final Submission"
      @ok="confirmRelock"
    >
      <p>
        Locking the Final Submission will replace its current dates and late policy with the assignment-level values.
        Any changes you've made will be lost.
      </p>
      <template #modal-footer="{ cancel, ok }">
        <b-button size="sm" @click="cancel()">
          Cancel
        </b-button>
        <b-button size="sm" variant="primary" @click="ok()">
          Lock
        </b-button>
      </template>
    </b-modal>

    <b-modal
      id="modal-forge-settings"
      title="Forge Settings"
      size="lg"
      scrollable
      no-close-on-backdrop
      @show="onModalShow"
      @hidden="onModalHidden"
    >
      <div v-if="isLoading" class="text-center py-4">
        <b-spinner variant="primary"/>
        <p class="mt-2">Loading...</p>
      </div>

      <div v-else-if="!assignTos.length" class="text-center py-4">
        <p class="text-muted">No assign-to groups found for this assignment. Please configure the assignment deadlines
          first.</p>
      </div>

      <b-tabs v-else content-class="mt-3">
        <!-- Drafts Tab -->
        <b-tab :title-link-class="getTabClass('drafts')" active>
          <template #title>
            Drafts
          </template>

          <!-- Collapse/Expand All Toggle -->
          <div class="mb-3 d-flex justify-content-between">
            <b-button variant="outline-primary" size="sm" @click="addDraft">
              <b-icon icon="plus"/>
              Add Draft
            </b-button>
            <b-button
              variant="outline-secondary"
              size="sm"
              @click="toggleAllDrafts"
            >
              <b-icon :icon="allDraftsCollapsed ? 'chevron-down' : 'chevron-up'" class="mr-1"/>
              {{ allDraftsCollapsed ? 'Expand All' : 'Collapse All' }}
            </b-button>
          </div>

          <draggable
            :key="draftsKey"
            v-model="drafts"
            handle=".drag-handle"
            :move="onMoveCallback"
            @end="onDragEnd"
          >
            <div v-for="(draft, draftIndex) in drafts" :key="draft.uuid" class="mb-2">
              <!-- Draft Header - Always Visible -->
              <div
                class="border rounded-top p-2 d-flex justify-content-between align-items-center"
                :class="[
                  { 'rounded-bottom': collapsedDrafts.includes(draftIndex) },
                  hasDraftErrors(draftIndex) ? 'bg-danger text-white' : 'bg-light'
                ]"
                role="button"
                @click="toggleDraftCollapse(draftIndex)"
              >
                <div class="d-flex align-items-center">
                  <b-icon
                    v-if="!draft.isFinal"
                    icon="grip-vertical"
                    class="drag-handle mr-2"
                    style="cursor: grab;"
                    @click.stop
                  />
                  <b-icon :icon="collapsedDrafts.includes(draftIndex) ? 'chevron-right' : 'chevron-down'" class="mr-2"/>
                  <strong>{{ getDraftTitle(draft, draftIndex) }}</strong>

                  <!-- Lock Toggle for Final Submission -->
                  <span v-if="draft.isFinal" class="ml-2" @click.stop>
                    <span :id="`lock-toggle-${draft.uuid}`">
                      <b-icon
                        :icon="finalSubmissionLocked ? 'lock-fill' : 'unlock-fill'"
                        :class="[
                          'lock-icon',
                          canToggleLock ? 'lock-icon-enabled' : 'lock-icon-disabled',
                          finalSubmissionLocked ? 'text-primary' : 'text-secondary'
                        ]"
                        :style="{ cursor: canToggleLock ? 'pointer' : 'not-allowed', opacity: canToggleLock ? 1 : 0.5 }"
                        @click="canToggleLock ? toggleFinalSubmissionLock() : null"
                      />
                    </span>
                    <b-tooltip :target="`lock-toggle-${draft.uuid}`" delay="250" triggers="hover focus">
                      <template v-if="!canToggleLock">
                        Lock is only available when there are no other drafts.
                        Remove all drafts to enable locking to assignment-level values.
                      </template>
                      <template v-else-if="finalSubmissionLocked">
                        Dates and late policy are locked to assignment-level values.
                        Click to unlock and customize.
                      </template>
                      <template v-else>
                        Dates and late policy are unlocked.
                        Click to lock to assignment-level values.
                      </template>
                    </b-tooltip>
                  </span>

                  <span v-if="draft.assign_tos && draft.assign_tos.length" class="ml-2 font-weight-normal">
                    <small v-for="(assignTo, idx) in draft.assign_tos" :key="`header-${draft.uuid}-${idx}`">
                      <span v-if="assignTo.due_date">
                        {{ getGroupName(assignTo, idx) }}: {{ assignTo.due_date }} {{ assignTo.due_time }}<span
                        v-if="idx < draft.assign_tos.length - 1"
                      >, </span>
                      </span>
                    </small>
                  </span>
                </div>
                <b-button
                  v-if="!draft.isFinal"
                  variant="outline-danger"
                  size="sm"
                  :disabled="isCheckingSubmissions"
                  @click.stop="removeDraft(draftIndex)"
                >
                  <b-spinner v-if="isCheckingSubmissions && draftToDeleteIndex === draftIndex" small/>
                  <b-icon v-else icon="trash"/>
                </b-button>
              </div>

              <!-- Draft Content - Collapsible -->
              <b-collapse :visible="!collapsedDrafts.includes(draftIndex)">
                <div
                  :key="`draft-content-${draft.uuid}-${draft.late_policy}-${draftsKey}-${draft.isFinal ? finalSubmissionLocked : ''}`"
                  class="border border-top-0 rounded-bottom p-3"
                >
                  <b-form-group
                    label-cols-sm="4"
                    label-cols-lg="3"
                    :label-for="`draft_title_${draftIndex}`"
                    label="Draft Title"
                  >
                    <b-form-input
                      :id="`draft_title_${draftIndex}`"
                      v-model="drafts[draftIndex].title"
                      type="text"
                      :placeholder="getDraftPlaceholder(draftIndex)"
                    />
                  </b-form-group>

                  <!-- Late Policy (at draft level, before assign_tos) -->
                  <b-form-group
                    label-cols-sm="4"
                    label-cols-lg="3"
                    :label-for="`draft_late_policy_${draftIndex}`"
                  >
                    <template v-slot:label>
                      Late Policy*
                    </template>
                    <b-form-radio-group
                      :id="`draft_late_policy_${draftIndex}`"
                      v-model="drafts[draftIndex].late_policy"
                      stacked
                      :disabled="draft.isFinal && finalSubmissionLocked"
                      :class="{ 'is-invalid': hasError(`drafts.${draftIndex}.late_policy`) }"
                    >
                      <b-form-radio
                        :name="`draft_late_policy_${draftIndex}`"
                        value="not accepted"
                        :disabled="draft.isFinal && finalSubmissionLocked"
                        @click.native="!(draft.isFinal && finalSubmissionLocked) && setLatePolicy(draftIndex, 'not accepted')"
                      >
                        Do not accept late
                      </b-form-radio>
                      <b-form-radio
                        :name="`draft_late_policy_${draftIndex}`"
                        value="marked late"
                        :disabled="draft.isFinal && finalSubmissionLocked"
                        @click.native="!(draft.isFinal && finalSubmissionLocked) && setLatePolicy(draftIndex, 'marked late')"
                      >
                        Accept but mark late
                      </b-form-radio>
                      <b-form-radio
                        :name="`draft_late_policy_${draftIndex}`"
                        value="deduction"
                        :disabled="draft.isFinal && finalSubmissionLocked"
                        @click.native="!(draft.isFinal && finalSubmissionLocked) && setLatePolicy(draftIndex, 'deduction')"
                      >
                        Accept late with a deduction
                      </b-form-radio>
                    </b-form-radio-group>
                    <div v-if="hasError(`drafts.${draftIndex}.late_policy`)" class="invalid-feedback d-block">
                      {{ getError(`drafts.${draftIndex}.late_policy`) }}
                    </div>
                    <a
                      v-if="drafts[draftIndex].late_policy && drafts.length > 1 && !(draft.isFinal && finalSubmissionLocked)"
                      href=""
                      class="small"
                      @click.prevent="applyLatePolicyToAllDrafts(draftIndex)"
                    >
                      Apply to all drafts
                    </a>
                  </b-form-group>

                  <!-- Late Deduction Percent (only if deduction) -->
                  <b-form-group
                    v-if="drafts[draftIndex].late_policy === 'deduction'"
                    label-cols-sm="4"
                    label-cols-lg="3"
                    :label-for="`draft_late_deduction_percent_${draftIndex}`"
                    label="Late Deduction Percent*"
                  >
                    <b-form-row>
                      <b-col lg="4">
                        <b-form-input
                          :id="`draft_late_deduction_percent_${draftIndex}`"
                          v-model="drafts[draftIndex].late_deduction_percent"
                          type="text"
                          placeholder="0-100"
                          :disabled="draft.isFinal && finalSubmissionLocked"
                          :class="{ 'is-invalid': hasError(`drafts.${draftIndex}.late_deduction_percent`) }"
                          @input="clearError(`drafts.${draftIndex}.late_deduction_percent`)"
                        />
                        <div v-if="hasError(`drafts.${draftIndex}.late_deduction_percent`)"
                             class="invalid-feedback d-block"
                        >
                          {{ getError(`drafts.${draftIndex}.late_deduction_percent`) }}
                        </div>
                      </b-col>
                    </b-form-row>
                  </b-form-group>

                  <!-- Late Deduction Applied (only if deduction) -->
                  <b-form-group
                    v-if="drafts[draftIndex].late_policy === 'deduction'"
                    label-cols-sm="4"
                    label-cols-lg="3"
                    :label-for="`draft_late_deduction_applied_${draftIndex}`"
                  >
                    <template v-slot:label>
                      Late Deduction Applied*
                    </template>
                    <b-form-radio-group
                      :id="`draft_late_deduction_applied_${draftIndex}`"
                      v-model="drafts[draftIndex].late_deduction_applied_once"
                      stacked
                      :disabled="draft.isFinal && finalSubmissionLocked"
                      :class="{ 'is-invalid': hasError(`drafts.${draftIndex}.late_deduction_applied_once`) }"
                      @change="onLateDeductionAppliedChange(draftIndex, $event)"
                    >
                      <span
                        @click="!(draft.isFinal && finalSubmissionLocked) && (drafts[draftIndex].late_deduction_application_period = '')"
                      >
                        <b-form-radio
                          :name="`draft_late_deduction_applied_${draftIndex}`"
                          :value="true"
                          :disabled="draft.isFinal && finalSubmissionLocked"
                        >
                          Just once
                        </b-form-radio>
                      </span>
                      <b-form-radio
                        :name="`draft_late_deduction_applied_${draftIndex}`"
                        :value="false"
                        class="mt-2"
                        :disabled="draft.isFinal && finalSubmissionLocked"
                      >
                        <b-row class="align-items-center">
                          <b-col cols="auto" class="pr-1">
                            Every
                          </b-col>
                          <b-col cols="auto">
                            <b-form-input
                              :id="`draft_late_deduction_application_period_${draftIndex}`"
                              v-model="drafts[draftIndex].late_deduction_application_period"
                              type="text"
                              placeholder="e.g., 1 hour"
                              style="width: 120px"
                              :disabled="drafts[draftIndex].late_deduction_applied_once !== false || (draft.isFinal && finalSubmissionLocked)"
                              :class="{ 'is-invalid': hasError(`drafts.${draftIndex}.late_deduction_application_period`) }"
                              @click="!(draft.isFinal && finalSubmissionLocked) && (drafts[draftIndex].late_deduction_applied_once = false)"
                              @input="clearError(`drafts.${draftIndex}.late_deduction_application_period`)"
                            />
                          </b-col>
                        </b-row>
                      </b-form-radio>
                    </b-form-radio-group>
                    <div v-if="hasError(`drafts.${draftIndex}.late_deduction_applied_once`)"
                         class="invalid-feedback d-block"
                    >
                      {{ getError(`drafts.${draftIndex}.late_deduction_applied_once`) }}
                    </div>
                    <div v-if="hasError(`drafts.${draftIndex}.late_deduction_application_period`)"
                         class="invalid-feedback d-block"
                    >
                      {{ getError(`drafts.${draftIndex}.late_deduction_application_period`) }}
                    </div>
                  </b-form-group>

                  <!-- Draft Assign Tos -->
                  <div v-for="(assignTo, assignToIndex) in drafts[draftIndex].assign_tos"
                       :key="`draft-${drafts[draftIndex].uuid}-assignto-${assignToIndex}`"
                  >
                    <!-- Group Label -->
                    <div class="mb-2 mt-3 pb-2 border-bottom">
                      <strong>{{ getGroupName(assignTo, assignToIndex) }}</strong>
                    </div>

                    <!-- Available on -->
                    <b-form-group
                      label-cols-sm="4"
                      label-cols-lg="3"
                      :label-for="`draft_available_from_${draftIndex}_${assignToIndex}`"
                    >
                      <template v-slot:label>
                        Available on*
                      </template>
                      <b-form-row>
                        <b-col lg="7">
                          <b-form-datepicker
                            :id="`draft_available_from_${draftIndex}_${assignToIndex}`"
                            v-model="drafts[draftIndex].assign_tos[assignToIndex].available_from_date"
                            required
                            tabindex="0"
                            :min="min"
                            :disabled="draft.isFinal && finalSubmissionLocked"
                            class="datepicker"
                            :class="{ 'is-invalid': hasError(`drafts.${draftIndex}.assign_tos.${assignToIndex}.available_from_date`) }"
                            @input="clearError(`drafts.${draftIndex}.assign_tos.${assignToIndex}.available_from_date`)"
                          />
                          <div v-if="hasError(`drafts.${draftIndex}.assign_tos.${assignToIndex}.available_from_date`)"
                               class="invalid-feedback d-block"
                          >
                            {{ getError(`drafts.${draftIndex}.assign_tos.${assignToIndex}.available_from_date`) }}
                          </div>
                        </b-col>
                        <b-col>
                          <vue-timepicker
                            :id="`draft_available_from_time_${draftIndex}_${assignToIndex}`"
                            v-model="drafts[draftIndex].assign_tos[assignToIndex].available_from_time"
                            format="h:mm A"
                            manual-input
                            drop-direction="up"
                            :disabled="draft.isFinal && finalSubmissionLocked"
                            :class="{ 'is-invalid': hasError(`drafts.${draftIndex}.assign_tos.${assignToIndex}.available_from_time`) }"
                            input-class="custom-timepicker-class"
                            @input="clearError(`drafts.${draftIndex}.assign_tos.${assignToIndex}.available_from_time`)"
                          >
                            <template v-slot:icon>
                              <b-icon-clock/>
                            </template>
                          </vue-timepicker>
                          <div v-if="hasError(`drafts.${draftIndex}.assign_tos.${assignToIndex}.available_from_time`)"
                               class="invalid-feedback d-block"
                          >
                            {{ getError(`drafts.${draftIndex}.assign_tos.${assignToIndex}.available_from_time`) }}
                          </div>
                        </b-col>
                      </b-form-row>
                    </b-form-group>

                    <!-- Due Date -->
                    <b-form-group
                      label-cols-sm="4"
                      label-cols-lg="3"
                      :label-for="`draft_due_date_${draftIndex}_${assignToIndex}`"
                    >
                      <template v-slot:label>
                        Due Date*
                      </template>
                      <b-form-row>
                        <b-col lg="7">
                          <b-form-datepicker
                            :id="`draft_due_date_${draftIndex}_${assignToIndex}`"
                            v-model="drafts[draftIndex].assign_tos[assignToIndex].due_date"
                            required
                            tabindex="0"
                            :min="min"
                            :disabled="draft.isFinal && finalSubmissionLocked"
                            :class="{ 'is-invalid': hasError(`drafts.${draftIndex}.assign_tos.${assignToIndex}.due_date`) }"
                            class="datepicker"
                            @input="clearError(`drafts.${draftIndex}.assign_tos.${assignToIndex}.due_date`); clearError(`drafts.${draftIndex}.assign_tos.${assignToIndex}.due`)"
                          />
                          <div v-if="hasError(`drafts.${draftIndex}.assign_tos.${assignToIndex}.due_date`)"
                               class="invalid-feedback d-block"
                          >
                            {{ getError(`drafts.${draftIndex}.assign_tos.${assignToIndex}.due_date`) }}
                          </div>
                        </b-col>
                        <b-col>
                          <vue-timepicker
                            :id="`draft_due_time_${draftIndex}_${assignToIndex}`"
                            v-model="drafts[draftIndex].assign_tos[assignToIndex].due_time"
                            format="h:mm A"
                            manual-input
                            drop-direction="up"
                            :disabled="draft.isFinal && finalSubmissionLocked"
                            :class="{ 'is-invalid': hasError(`drafts.${draftIndex}.assign_tos.${assignToIndex}.due_time`) }"
                            input-class="custom-timepicker-class"
                            @input="clearError(`drafts.${draftIndex}.assign_tos.${assignToIndex}.due_time`); clearError(`drafts.${draftIndex}.assign_tos.${assignToIndex}.due`)"
                          >
                            <template v-slot:icon>
                              <b-icon-clock/>
                            </template>
                          </vue-timepicker>
                          <div v-if="hasError(`drafts.${draftIndex}.assign_tos.${assignToIndex}.due_time`)"
                               class="invalid-feedback d-block"
                          >
                            {{ getError(`drafts.${draftIndex}.assign_tos.${assignToIndex}.due_time`) }}
                          </div>
                        </b-col>
                      </b-form-row>
                    </b-form-group>

                    <!-- Final Submission Deadline (only if late_policy is marked late or deduction) -->
                    <b-form-group
                      v-if="drafts[draftIndex].late_policy === 'marked late' || drafts[draftIndex].late_policy === 'deduction'"
                      label-cols-sm="4"
                      label-cols-lg="3"
                      :label-for="`draft_final_submission_deadline_${draftIndex}_${assignToIndex}`"
                    >
                      <template v-slot:label>
                        Final Submission Deadline*
                      </template>
                      <b-form-row>
                        <b-col lg="7">
                          <b-form-datepicker
                            :id="`draft_final_submission_deadline_${draftIndex}_${assignToIndex}`"
                            v-model="drafts[draftIndex].assign_tos[assignToIndex].final_submission_deadline_date"
                            required
                            tabindex="0"
                            :min="min"
                            :disabled="draft.isFinal && finalSubmissionLocked"
                            :class="{ 'is-invalid': hasError(`drafts.${draftIndex}.assign_tos.${assignToIndex}.final_submission_deadline_date`) }"
                            class="datepicker"
                            @input="clearError(`drafts.${draftIndex}.assign_tos.${assignToIndex}.final_submission_deadline_date`); clearError(`drafts.${draftIndex}.assign_tos.${assignToIndex}.final_submission_deadline`)"
                          />
                          <div
                            v-if="hasError(`drafts.${draftIndex}.assign_tos.${assignToIndex}.final_submission_deadline_date`)"
                            class="invalid-feedback d-block"
                          >
                            {{
                              getError(`drafts.${draftIndex}.assign_tos.${assignToIndex}.final_submission_deadline_date`)
                            }}
                          </div>
                        </b-col>
                        <b-col>
                          <vue-timepicker
                            :id="`draft_final_submission_deadline_time_${draftIndex}_${assignToIndex}`"
                            v-model="drafts[draftIndex].assign_tos[assignToIndex].final_submission_deadline_time"
                            format="h:mm A"
                            manual-input
                            drop-direction="up"
                            :disabled="draft.isFinal && finalSubmissionLocked"
                            :class="{ 'is-invalid': hasError(`drafts.${draftIndex}.assign_tos.${assignToIndex}.final_submission_deadline_time`) }"
                            input-class="custom-timepicker-class"
                            @input="clearError(`drafts.${draftIndex}.assign_tos.${assignToIndex}.final_submission_deadline_time`); clearError(`drafts.${draftIndex}.assign_tos.${assignToIndex}.final_submission_deadline`)"
                          >
                            <template v-slot:icon>
                              <b-icon-clock/>
                            </template>
                          </vue-timepicker>
                          <div
                            v-if="hasError(`drafts.${draftIndex}.assign_tos.${assignToIndex}.final_submission_deadline_time`)"
                            class="invalid-feedback d-block"
                          >
                            {{
                              getError(`drafts.${draftIndex}.assign_tos.${assignToIndex}.final_submission_deadline_time`)
                            }}
                          </div>
                        </b-col>
                      </b-form-row>
                    </b-form-group>
                  </div>

                  <!-- Extensions (at draft/question level) -->
                  <div class="extensions-section mt-4 p-3 border rounded bg-light">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <div>
                        <b-icon icon="person-plus" class="mr-1"/>
                        <strong>Extensions</strong>
                        <b-badge variant="secondary" class="ml-1">
                          {{ getExtensionCount(draftIndex) }}
                        </b-badge>
                      </div>
                      <b-button
                        variant="outline-secondary"
                        size="sm"
                        @click="addExtension(draftIndex)"
                      >
                        <b-icon icon="plus"/>
                        Add Extension
                      </b-button>
                    </div>

                    <p v-if="!getExtensions(draftIndex).length" class="text-muted small mb-0">
                      No extensions have been added for this draft.
                    </p>

                    <!-- Extensions List (Editable) -->
                    <div v-if="getExtensions(draftIndex).length">
                      <div
                        v-for="(extension, extIndex) in getExtensions(draftIndex)"
                        :key="`ext-${draftIndex}-${extIndex}`"
                        class="border rounded p-3 mb-2"
                        :class="{ 'border-danger': hasExtensionErrors(draftIndex, extIndex) }"
                      >
                        <div class="d-flex justify-content-between align-items-start mb-2">
                          <strong>Extension {{ extIndex + 1 }}</strong>
                          <b-button
                            variant="outline-danger"
                            size="sm"
                            @click="removeExtension(draftIndex, extIndex)"
                          >
                            <b-icon icon="trash"/>
                          </b-button>
                        </div>

                        <b-form-group label="Student*"
                                      label-cols-sm="4"
                                      label-cols-lg="3"
                                      class="mb-2"
                        >
                          <select
                            class="form-control"
                            :class="{ 'is-invalid': hasError(`drafts.${draftIndex}.extensions.${extIndex}.user_id`) }"
                            :value="drafts[draftIndex].extensions[extIndex].user_id"
                            @change="onExtensionStudentSelect(draftIndex, extIndex, $event)"
                          >
                            <option :value="null" disabled selected>Select a student...</option>
                            <option
                              v-for="student in getAvailableStudentsForExtension(draftIndex, extIndex)"
                              :key="student.user_id"
                              :value="student.user_id"
                            >
                              {{ student.name }}
                            </option>
                          </select>
                          <div
                            v-if="hasError(`drafts.${draftIndex}.extensions.${extIndex}.user_id`)"
                            class="invalid-feedback d-block"
                          >
                            {{ getError(`drafts.${draftIndex}.extensions.${extIndex}.user_id`) }}
                          </div>
                        </b-form-group>

                        <b-form-group label="Due Date*"  label-cols-sm="4"
                                      label-cols-lg="3"
                                      class="mb-2">
                          <b-form-row>
                            <b-col lg="8">
                              <b-form-datepicker
                                v-model="drafts[draftIndex].extensions[extIndex].due_date"
                                :min="min"
                                class="datepicker"
                                :class="{ 'is-invalid': hasError(`drafts.${draftIndex}.extensions.${extIndex}.due_date`) }"
                                @input="clearError(`drafts.${draftIndex}.extensions.${extIndex}.due_date`); clearError(`drafts.${draftIndex}.extensions.${extIndex}.due`)"
                              />
                              <div
                                v-if="hasError(`drafts.${draftIndex}.extensions.${extIndex}.due_date`)"
                                class="invalid-feedback d-block"
                              >
                                {{ getError(`drafts.${draftIndex}.extensions.${extIndex}.due_date`) }}
                              </div>
                            </b-col>
                              <vue-timepicker
                                v-model="drafts[draftIndex].extensions[extIndex].due_time"
                                format="h:mm A"
                                manual-input
                                drop-direction="up"
                                input-class="custom-timepicker-class"
                                :class="{ 'is-invalid': hasError(`drafts.${draftIndex}.extensions.${extIndex}.due_time`) }"
                                @input="clearError(`drafts.${draftIndex}.extensions.${extIndex}.due_time`); clearError(`drafts.${draftIndex}.extensions.${extIndex}.due`)"
                              >
                                <template v-slot:icon>
                                  <b-icon-clock/>
                                </template>
                              </vue-timepicker>
                              <div
                                v-if="hasError(`drafts.${draftIndex}.extensions.${extIndex}.due_time`)"
                                class="invalid-feedback d-block"
                              >
                                {{ getError(`drafts.${draftIndex}.extensions.${extIndex}.due_time`) }}
                              </div>
                          </b-form-row>
                          <div
                            v-if="hasError(`drafts.${draftIndex}.extensions.${extIndex}.due`)"
                            class="invalid-feedback d-block"
                          >
                            {{ getError(`drafts.${draftIndex}.extensions.${extIndex}.due`) }}
                          </div>
                        </b-form-group>

                        <b-form-group
                          v-if="drafts[draftIndex].late_policy === 'marked late' || drafts[draftIndex].late_policy === 'deduction'"
                          label="Final Submission Deadline*"
                          label-cols-sm="3"
                          class="mb-0"
                        >
                          <b-form-row>
                            <b-col lg="8">
                              <b-form-datepicker
                                v-model="drafts[draftIndex].extensions[extIndex].final_submission_deadline_date"
                                :min="min"
                                class="datepicker"
                                :class="{ 'is-invalid': hasError(`drafts.${draftIndex}.extensions.${extIndex}.final_submission_deadline_date`) }"
                                @input="clearError(`drafts.${draftIndex}.extensions.${extIndex}.final_submission_deadline_date`); clearError(`drafts.${draftIndex}.extensions.${extIndex}.final_submission_deadline`)"
                              />
                              <div
                                v-if="hasError(`drafts.${draftIndex}.extensions.${extIndex}.final_submission_deadline_date`)"
                                class="invalid-feedback d-block"
                              >
                                {{
                                  getError(`drafts.${draftIndex}.extensions.${extIndex}.final_submission_deadline_date`)
                                }}
                              </div>
                            </b-col>
                              <vue-timepicker
                                v-model="drafts[draftIndex].extensions[extIndex].final_submission_deadline_time"
                                format="h:mm A"
                                manual-input
                                drop-direction="up"
                                input-class="custom-timepicker-class"
                                :class="{ 'is-invalid': hasError(`drafts.${draftIndex}.extensions.${extIndex}.final_submission_deadline_time`) }"
                                @input="clearError(`drafts.${draftIndex}.extensions.${extIndex}.final_submission_deadline_time`); clearError(`drafts.${draftIndex}.extensions.${extIndex}.final_submission_deadline`)"
                              >
                                <template v-slot:icon>
                                  <b-icon-clock/>
                                </template>
                              </vue-timepicker>
                              <div
                                v-if="hasError(`drafts.${draftIndex}.extensions.${extIndex}.final_submission_deadline_time`)"
                                class="invalid-feedback d-block"
                              >
                                {{
                                  getError(`drafts.${draftIndex}.extensions.${extIndex}.final_submission_deadline_time`)
                                }}
                              </div>
                          </b-form-row>
                          <div
                            v-if="hasError(`drafts.${draftIndex}.extensions.${extIndex}.final_submission_deadline`)"
                            class="invalid-feedback d-block"
                          >
                            {{ getError(`drafts.${draftIndex}.extensions.${extIndex}.final_submission_deadline`) }}
                          </div>
                        </b-form-group>
                      </div>
                    </div>
                  </div>
                  <!-- End Extensions Section -->
                </div>
              </b-collapse>
            </div>
          </draggable>

        </b-tab>

        <!-- Submission Settings Tab -->
        <b-tab :title-link-class="getTabClass('submission')">
          <template #title>
            Submission
          </template>

          <b-form-group :class="{ 'is-invalid': hasError('settings.autoSubmission') }">
            <b-form-checkbox v-model="settings.autoSubmission"
                             @change="clearError('settings.autoSubmission'); onAutoSubmissionChange($event)"
            >
              Auto-submit at deadline
              <QuestionCircleTooltip id="auto-submission-tooltip"/>
              <b-tooltip target="auto-submission-tooltip" delay="250" triggers="hover focus">
                Automatically submit student work when the deadline is reached.
              </b-tooltip>
            </b-form-checkbox>
            <div v-if="hasError('settings.autoSubmission')" class="invalid-feedback d-block">
              {{ getError('settings.autoSubmission') }}
            </div>
          </b-form-group>

          <b-form-group :class="{ 'is-invalid': hasError('settings.preventAfterDueDate') }">
            <b-form-checkbox v-model="settings.preventAfterDueDate" :disabled="!settings.autoSubmission"
                             @change="clearError('settings.preventAfterDueDate')"
            >
              Prevent submissions after due date
              <QuestionCircleTooltip id="prevent-after-due-tooltip"/>
              <b-tooltip target="prevent-after-due-tooltip" delay="250" triggers="hover focus">
                Students cannot submit work after the due date has passed.
              </b-tooltip>
            </b-form-checkbox>
            <div v-if="hasError('settings.preventAfterDueDate')" class="invalid-feedback d-block">
              {{ getError('settings.preventAfterDueDate') }}
            </div>
          </b-form-group>

          <b-form-group :class="{ 'is-invalid': hasError('settings.autoAccept') }">
            <b-form-checkbox v-model="settings.autoAccept" @change="clearError('settings.autoAccept')">
              Auto-accept AI feedback
              <QuestionCircleTooltip id="auto-accept-tooltip"/>
              <b-tooltip target="auto-accept-tooltip" delay="250" triggers="hover focus">
                Automatically accept AI-generated feedback without instructor review.
              </b-tooltip>
            </b-form-checkbox>
            <div v-if="hasError('settings.autoAccept')" class="invalid-feedback d-block">
              {{ getError('settings.autoAccept') }}
            </div>
          </b-form-group>
        </b-tab>

        <!-- Analytics Tab -->
        <b-tab :title-link-class="getTabClass('analytics')">
          <template #title>
            Analytics
          </template>

          <b-form-group
            label="Show individual analytics graphs to students?"
            :class="{ 'is-invalid': hasError('settings.showAnalytics') }"
          >
            <b-form-radio-group v-model="settings.showAnalytics" stacked @change="clearError('settings.showAnalytics')">
              <b-form-radio value="never">Never</b-form-radio>
              <b-form-radio value="after_grade">After grade is released</b-form-radio>
              <b-form-radio value="always">Always</b-form-radio>
            </b-form-radio-group>
            <div v-if="hasError('settings.showAnalytics')" class="invalid-feedback d-block">
              {{ getError('settings.showAnalytics') }}
            </div>
          </b-form-group>
        </b-tab>

        <!-- Files Tab -->
        <b-tab :title-link-class="getTabClass('files')">
          <template #title>
            Files
          </template>

          <b-form-group
            label="Main File Type"
            label-cols-sm="4"
            label-cols-lg="3"
            :class="{ 'is-invalid': hasError('settings.mainFileType') }"
          >
            <b-form-select v-model="settings.mainFileType" :options="fileTypeOptions"
                           @change="clearError('settings.mainFileType')"
            />
            <div v-if="hasError('settings.mainFileType')" class="invalid-feedback d-block">
              {{ getError('settings.mainFileType') }}
            </div>
          </b-form-group>

          <b-form-group :class="{ 'is-invalid': hasError('settings.allowImport') }">
            <b-form-checkbox v-model="settings.allowImport" @change="clearError('settings.allowImport')">
              Allow students to import the main document
            </b-form-checkbox>
            <div v-if="hasError('settings.allowImport')" class="invalid-feedback d-block">
              {{ getError('settings.allowImport') }}
            </div>
          </b-form-group>

          <hr>

          <p class="font-weight-bold">
            Additional Files
            <QuestionCircleTooltip id="additional-files-tooltip"/>
            <b-tooltip target="additional-files-tooltip" delay="250" triggers="hover focus">
              Select additional file types students can create.
            </b-tooltip>
          </p>

          <b-form-group :class="{ 'is-invalid': hasError('settings.additionalFiles') }">
            <b-form-checkbox-group v-model="settings.additionalFiles" stacked
                                   @change="clearError('settings.additionalFiles')"
            >
              <b-form-checkbox value="presentation">Presentation</b-form-checkbox>
              <b-form-checkbox value="spreadsheet">Spreadsheet</b-form-checkbox>
              <b-form-checkbox value="document">Document</b-form-checkbox>
              <b-form-checkbox value="draw">Draw</b-form-checkbox>
            </b-form-checkbox-group>
            <div v-if="hasError('settings.additionalFiles')" class="invalid-feedback d-block">
              {{ getError('settings.additionalFiles') }}
            </div>
          </b-form-group>

          <hr>

          <b-form-group :class="{ 'is-invalid': hasError('settings.uploadFile') }">
            <b-form-checkbox v-model="settings.uploadFile" @change="clearError('settings.uploadFile')">
              Allow students to upload attachment files
            </b-form-checkbox>
            <div v-if="hasError('settings.uploadFile')" class="invalid-feedback d-block">
              {{ getError('settings.uploadFile') }}
            </div>
          </b-form-group>
        </b-tab>
      </b-tabs>

      <template #modal-footer="{ cancel }">
        <b-button size="sm" @click="cancel()">
          Cancel
        </b-button>
        <b-button size="sm" variant="primary" :disabled="isSaving" @click="saveForgeSettings">
          <b-spinner v-if="isSaving" small class="mr-1"/>
          Save
        </b-button>
      </template>
    </b-modal>
  </div>
</template>

<script>
import axios from 'axios'
import { v4 as uuidv4 } from 'uuid'
import draggable from 'vuedraggable'
import VueTimepicker from 'vue2-timepicker/src/vue-timepicker.vue'
import AllFormErrors from '~/components/AllFormErrors'

export default {
  name: 'ForgeSettings',
  components: {
    draggable,
    VueTimepicker,
    AllFormErrors
  },
  props: {
    currentPage: {
      type: Number,
      default: 0
    },
    assignmentId: {
      type: Number,
      required: true
    },
    questionId: {
      type: Number,
      required: true
    },
    currentDraftQuestionId: {
      type: Number,
      default: null
    }
  },
  data () {
    return {
      isCheckingSubmissions: false,
      isLoading: false,
      isLoaded: false,
      isSaving: false,
      isDeleting: false,
      courseId: null,
      assignTos: [],
      drafts: [],
      collapsedDrafts: [],
      allDraftsCollapsed: false,
      errors: {},
      allFormErrors: [],
      min: '',
      // Draft deletion
      draftToDelete: null,
      draftToDeleteIndex: null,
      draftSubmissionCount: 0,
      deletedDraftQuestionIds: [],
      draftsKey: 0,
      previousLatePolicies: {},
      // Extensions
      enrollments: [],
      // Assignment-level late policy (from API)
      assignmentLatePolicy: '',
      assignmentLateDeductionPercent: null,
      assignmentLateDeductionAppliedOnce: null,
      assignmentLateDeductionApplicationPeriod: null,
      // Final Submission lock
      finalSubmissionLocked: true,
      settings: {
        // Submission settings
        autoSubmission: false,
        preventAfterDueDate: false,
        autoAccept: false,
        // Analytics settings
        showAnalytics: 'never',
        // Template settings
        mainFileType: 'document',
        allowImport: false,
        additionalFiles: [],
        uploadFile: false
      },
      fileTypeOptions: [
        { value: 'document', text: 'Document' },
        { value: 'spreadsheet', text: 'Spreadsheet' },
        { value: 'presentation', text: 'Presentation' },
        { value: 'draw', text: 'Draw' },
        { value: 'image', text: 'Image' }
      ]
    }
  },
  computed: {
    hasDraftsTabErrors () {
      return Object.keys(this.errors).some(key => key.startsWith('drafts.'))
    },
    hasSubmissionTabErrors () {
      const fields = ['autoSubmission', 'preventAfterDueDate', 'autoAccept']
      return fields.some(field => this.errors[`settings.${field}`])
    },
    hasAnalyticsTabErrors () {
      return !!this.errors['settings.showAnalytics']
    },
    hasFilesTabErrors () {
      const fields = ['mainFileType', 'allowImport', 'additionalFiles', 'uploadFile']
      return fields.some(field => this.errors[`settings.${field}`])
    },
    hasOtherDrafts () {
      return this.drafts.some(d => !d.isFinal)
    },
    canToggleLock () {
      return !this.hasOtherDrafts
    }
  },
  watch: {
    hasOtherDrafts (newVal) {
      if (newVal && this.finalSubmissionLocked) {
        this.finalSubmissionLocked = false
      }
    }
  },
  mounted () {
    this.min = this.$moment(this.$moment(), 'YYYY-MM-DD').format('YYYY-MM-DD')
  },
  methods: {
    generateUuid () {
      return uuidv4()
    },

    createFinalSubmission () {
      const finalDraft = {
        uuid: this.generateUuid(),
        title: '',
        isFinal: true,
        late_policy: this.finalSubmissionLocked ? this.assignmentLatePolicy : '',
        late_deduction_percent: this.finalSubmissionLocked ? this.assignmentLateDeductionPercent : null,
        late_deduction_applied_once: this.finalSubmissionLocked ? this.assignmentLateDeductionAppliedOnce : null,
        late_deduction_application_period: this.finalSubmissionLocked ? this.assignmentLateDeductionApplicationPeriod : null,
        extensions: [],
        assign_tos: []
      }

      // Initialize assign_tos based on assignment's assign_tos
      // Auto-populate dates from assignment level for Final Submission
      for (let i = 0; i < this.assignTos.length; i++) {
        const assignTo = this.assignTos[i]
        finalDraft.assign_tos.push({
          groups: assignTo.groups,
          available_from_date: assignTo.available_from_date || '',
          available_from_time: assignTo.available_from_time || '',
          due_date: assignTo.due_date || '',
          due_time: assignTo.due_time || '',
          final_submission_deadline_date: assignTo.final_submission_deadline_date || '',
          final_submission_deadline_time: assignTo.final_submission_deadline_time || ''
        })
      }

      return finalDraft
    },

    async loadForgeSettings () {
      this.isLoading = true
      this.deletedDraftQuestionIds = []
      try {
        // Load forge settings
        const { data } = await axios.get(`/api/assignments/${this.assignmentId}/question/${this.questionId}/forge-settings`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return
        }

        // Get course_id from assignment summary and load enrollments
        await this.getAssignmentSummary()
        if (this.courseId) {
          await this.loadEnrollments()
        }

        // Store assignment-level late policy from API
        this.assignmentLatePolicy = data.late_policy || ''
        this.assignmentLateDeductionPercent = data.late_deduction_percent || null
        this.assignmentLateDeductionAppliedOnce = typeof data.late_deduction_applied_once !== 'undefined'
          ? (data.late_deduction_applied_once !== null ? !!data.late_deduction_applied_once : null)
          : null
        this.assignmentLateDeductionApplicationPeriod = data.late_deduction_application_period || null

        // Store assign_tos from assignment
        this.assignTos = data.assign_tos || []

        // Format assign_tos
        for (let i = 0; i < this.assignTos.length; i++) {
          this.assignTos[i].groups = this.assignTos[i].formatted_groups || this.assignTos[i].groups || []
          if (this.assignTos[i].available_from_time) {
            this.assignTos[i].available_from_time = this.reformatTime(this.assignTos[i].available_from_time)
          }
          if (this.assignTos[i].due_time) {
            this.assignTos[i].due_time = this.reformatTime(this.assignTos[i].due_time)
          }
          if (this.assignTos[i].final_submission_deadline_time) {
            this.assignTos[i].final_submission_deadline_time = this.reformatTime(this.assignTos[i].final_submission_deadline_time)
          }
        }

        // Load existing drafts or create default Final Submission
        if (data.drafts && data.drafts.length) {
          this.drafts = data.drafts

          // Ensure all drafts have UUIDs, isFinal flag, late policy fields, and extensions
          for (let i = 0; i < this.drafts.length; i++) {
            const draft = this.drafts[i]
            if (!draft.uuid) {
              draft.uuid = this.generateUuid()
            }
            if (typeof draft.isFinal === 'undefined') {
              draft.isFinal = false
            }
            // Initialize late policy fields if missing
            if (typeof draft.late_policy === 'undefined') {
              draft.late_policy = ''
            }
            if (typeof draft.late_deduction_percent === 'undefined') {
              draft.late_deduction_percent = null
            }
            if (typeof draft.late_deduction_applied_once === 'undefined') {
              draft.late_deduction_applied_once = null
            } else if (draft.late_deduction_applied_once !== null) {
              draft.late_deduction_applied_once = !!draft.late_deduction_applied_once
            }
            if (typeof draft.late_deduction_application_period === 'undefined') {
              draft.late_deduction_application_period = null
            }
            // Initialize extensions array at draft level if missing
            if (!draft.extensions) {
              draft.extensions = []
            }
            // Store initial late policy for revert functionality
            this.previousLatePolicies[i] = draft.late_policy
          }

          // Format draft times
          for (let i = 0; i < this.drafts.length; i++) {
            const draft = this.drafts[i]
            if (draft.assign_tos) {
              for (let j = 0; j < draft.assign_tos.length; j++) {
                const assignTo = draft.assign_tos[j]
                if (assignTo.available_from_time) {
                  assignTo.available_from_time = this.reformatTime(assignTo.available_from_time)
                }
                if (assignTo.due_time) {
                  assignTo.due_time = this.reformatTime(assignTo.due_time)
                }
                if (assignTo.final_submission_deadline_time) {
                  assignTo.final_submission_deadline_time = this.reformatTime(assignTo.final_submission_deadline_time)
                }
                // Initialize final submission deadline fields if missing
                if (typeof assignTo.final_submission_deadline_date === 'undefined') {
                  assignTo.final_submission_deadline_date = ''
                }
                if (typeof assignTo.final_submission_deadline_time === 'undefined') {
                  assignTo.final_submission_deadline_time = ''
                }
              }
            }
            // Format extension times
            if (draft.extensions) {
              for (let j = 0; j < draft.extensions.length; j++) {
                const ext = draft.extensions[j]
                if (ext.due_time && ext.due_time.includes(':') && !ext.due_time.includes('AM') && !ext.due_time.includes('PM')) {
                  ext.due_time = this.reformatTime(ext.due_time)
                }
                if (ext.final_submission_deadline_time && ext.final_submission_deadline_time.includes(':') && !ext.final_submission_deadline_time.includes('AM') && !ext.final_submission_deadline_time.includes('PM')) {
                  ext.final_submission_deadline_time = this.reformatTime(ext.final_submission_deadline_time)
                }
              }
            }
          }

          this.syncDraftAssignTos()

          // Ensure Final Submission exists
          const hasFinal = this.drafts.some(d => d.isFinal)
          if (!hasFinal) {
            this.drafts.push(this.createFinalSubmission())
          }

          // Determine lock state: locked by default only if no other drafts
          const hasNonFinal = this.drafts.some(d => !d.isFinal)
          if (hasNonFinal) {
            this.finalSubmissionLocked = false
          } else {
            // Load lock state from server if available, default to locked
            this.finalSubmissionLocked = typeof data.final_submission_locked !== 'undefined'
              ? data.final_submission_locked
              : true
          }

          // If locked, apply assignment-level values to Final Submission
          if (this.finalSubmissionLocked) {
            this.applyAssignmentLevelToFinalSubmission()
          }

          // Find the index of the current draft to expand
          let expandIndex = 0 // Default to first draft
          if (this.currentDraftQuestionId) {
            const currentDraftIndex = this.drafts.findIndex(d => d.question_id === this.currentDraftQuestionId)
            if (currentDraftIndex !== -1) {
              expandIndex = currentDraftIndex
            } else {
              // If no question_id match, check if current question is the main forge question (Final Submission)
              const finalIndex = this.drafts.findIndex(d => d.isFinal)
              if (this.currentDraftQuestionId === this.questionId && finalIndex !== -1) {
                expandIndex = finalIndex
              }
            }
          }

          // Collapse all drafts except the one being viewed
          this.collapsedDrafts = this.drafts
            .map((_, index) => index)
            .filter(index => index !== expandIndex)
          this.allDraftsCollapsed = false
        } else {
          // No drafts exist - create default Final Submission (locked by default)
          this.finalSubmissionLocked = true
          this.drafts = [this.createFinalSubmission()]
        }

        // Load other settings
        if (data.settings) {
          this.settings = { ...this.settings, ...data.settings }
        }

        this.isLoaded = true
      } catch (error) {
        this.$noty.error(error.message)
      } finally {
        this.isLoading = false
      }
    },

    reformatTime (time) {
      return this.$moment(time, 'HH:mm:ss').format('h:mm A')
    },

    resetState () {
      this.isCheckingSubmissions = false
      this.isLoaded = false
      this.courseId = null
      this.drafts = []
      this.assignTos = []
      this.collapsedDrafts = []
      this.allDraftsCollapsed = false
      this.errors = {}
      this.allFormErrors = []
      this.draftToDelete = null
      this.draftToDeleteIndex = null
      this.draftSubmissionCount = 0
      this.draftsKey = 0
      this.previousLatePolicies = {}
      this.enrollments = []
      this.assignmentLatePolicy = ''
      this.assignmentLateDeductionPercent = null
      this.assignmentLateDeductionAppliedOnce = null
      this.assignmentLateDeductionApplicationPeriod = null
      this.finalSubmissionLocked = true
      this.settings = {
        autoSubmission: false,
        preventAfterDueDate: false,
        autoAccept: false,
        showAnalytics: 'never',
        mainFileType: 'document',
        allowImport: false,
        additionalFiles: [],
        uploadFile: false
      }
    },

    onModalShow () {
      // Only load if not already loaded (prevents reload after error modal closes)
      if (!this.isLoaded) {
        this.loadForgeSettings()
      }
    },

    onModalHidden () {
      // Emit event with deleted draft question IDs so parent can update
      if (this.deletedDraftQuestionIds.length > 0) {
        this.$emit('drafts-deleted', this.deletedDraftQuestionIds)
      }
      this.resetState()
    },

    // --- Final Submission Lock ---

    toggleFinalSubmissionLock () {
      if (!this.canToggleLock) {
        return
      }

      if (this.finalSubmissionLocked) {
        // Unlocking - just unlock, no confirmation needed
        this.finalSubmissionLocked = false
      } else {
        // Re-locking - show confirmation since user may have made edits
        this.$bvModal.show('modal-confirm-relock')
      }
    },

    confirmRelock () {
      this.finalSubmissionLocked = true
      this.applyAssignmentLevelToFinalSubmission()
      this.draftsKey++
      this.$bvModal.hide('modal-confirm-relock')
    },

    applyAssignmentLevelToFinalSubmission () {
      const finalDraft = this.drafts.find(d => d.isFinal)
      if (!finalDraft) {
        return
      }

      // Apply assignment-level late policy
      this.$set(finalDraft, 'late_policy', this.assignmentLatePolicy)
      this.$set(finalDraft, 'late_deduction_percent', this.assignmentLateDeductionPercent)
      this.$set(finalDraft, 'late_deduction_applied_once', this.assignmentLateDeductionAppliedOnce !== null ? !!this.assignmentLateDeductionAppliedOnce : null)
      this.$set(finalDraft, 'late_deduction_application_period', this.assignmentLateDeductionApplicationPeriod)
      // Apply assignment-level assign_to dates (but preserve extensions at draft level)
      if (finalDraft.assign_tos) {
        for (let i = 0; i < finalDraft.assign_tos.length; i++) {
          if (i < this.assignTos.length) {
            const assignTo = this.assignTos[i]
            this.$set(finalDraft.assign_tos[i], 'available_from_date', assignTo.available_from_date || '')
            this.$set(finalDraft.assign_tos[i], 'available_from_time', assignTo.available_from_time || '')
            this.$set(finalDraft.assign_tos[i], 'due_date', assignTo.due_date || '')
            this.$set(finalDraft.assign_tos[i], 'due_time', assignTo.due_time || '')
            this.$set(finalDraft.assign_tos[i], 'final_submission_deadline_date', assignTo.final_submission_deadline_date || '')
            this.$set(finalDraft.assign_tos[i], 'final_submission_deadline_time', assignTo.final_submission_deadline_time || '')
          }
        }
      }
    },

    // --- Late Policy ---

    // Store previous late policy before change
    storePreviousLatePolicy (draftIndex) {
      this.previousLatePolicies[draftIndex] = this.drafts[draftIndex].late_policy
    },

    // Set late policy with click handler
    setLatePolicy (draftIndex, value) {
      this.$set(this.drafts[draftIndex], 'late_policy', value)
      this.onLatePolicyChange(draftIndex, value)
      this.$forceUpdate()
    },

    // Late policy change handler
    onLatePolicyChange (draftIndex, newValue) {
      const draft = this.drafts[draftIndex]

      // Clear the error
      this.clearError(`drafts.${draftIndex}.late_policy`)

      // Show note if auto-submit is enabled and user selected a late-accepting policy
      if (this.settings.autoSubmission && (newValue === 'marked late' || newValue === 'deduction')) {
        this.$noty.info('Note: "Auto-submit at deadline" will be disabled since this late policy accepts late submissions.')
      }

      if (newValue === 'not accepted') {
        // Clear late deduction fields
        this.$set(draft, 'late_deduction_percent', null)
        this.$set(draft, 'late_deduction_applied_once', null)
        this.$set(draft, 'late_deduction_application_period', null)
        // Clear final submission deadline fields
        if (draft.assign_tos) {
          for (let i = 0; i < draft.assign_tos.length; i++) {
            this.$set(draft.assign_tos[i], 'final_submission_deadline_date', '')
            this.$set(draft.assign_tos[i], 'final_submission_deadline_time', '')
          }
        }
      } else if (newValue === 'marked late') {
        // Clear late deduction fields but keep final submission deadline
        this.$set(draft, 'late_deduction_percent', null)
        this.$set(draft, 'late_deduction_applied_once', null)
        this.$set(draft, 'late_deduction_application_period', null)
      } else if (newValue === 'deduction') {
        // Initialize deduction fields
        if (draft.late_deduction_applied_once === null) {
          this.$set(draft, 'late_deduction_applied_once', true)
        }
      }
    },

    // Late deduction applied change handler
    onLateDeductionAppliedChange (draftIndex, value) {
      this.clearError(`drafts.${draftIndex}.late_deduction_applied_once`)
      this.$set(this.drafts[draftIndex], 'late_deduction_applied_once', value)
      if (value === true) {
        this.$set(this.drafts[draftIndex], 'late_deduction_application_period', '')
      }
      this.$forceUpdate()
    },

    // Apply late policy from one draft to all others
    applyLatePolicyToAllDrafts (sourceDraftIndex) {
      const sourceDraft = this.drafts[sourceDraftIndex]

      const updatedDrafts = this.drafts.map((draft, i) => {
        if (i === sourceDraftIndex) {
          return draft
        }

        // Skip locked Final Submission
        if (draft.isFinal && this.finalSubmissionLocked) {
          return draft
        }

        return {
          ...draft,
          late_policy: sourceDraft.late_policy,
          late_deduction_percent: sourceDraft.late_deduction_percent,
          late_deduction_applied_once: sourceDraft.late_deduction_applied_once,
          late_deduction_application_period: sourceDraft.late_deduction_application_period
        }
      })

      this.drafts = updatedDrafts
      this.draftsKey++

      // Clear late policy related errors for all drafts
      const newErrors = {}
      for (const key in this.errors) {
        // Keep errors that are not late policy related
        if (!key.includes('.late_policy') &&
          !key.includes('.late_deduction_percent') &&
          !key.includes('.late_deduction_applied_once') &&
          !key.includes('.late_deduction_application_period')) {
          newErrors[key] = this.errors[key]
        }
      }
      this.errors = newErrors

      this.$noty.success('Late policy applied to all drafts.')
    },

    // Auto-submission toggle handler
    onAutoSubmissionChange (checked) {
      if (checked) {
        // Check if any draft has a late policy other than 'not accepted'
        const hasLateAcceptingPolicy = this.drafts.some(draft =>
          draft.late_policy === 'marked late' || draft.late_policy === 'deduction'
        )

        if (hasLateAcceptingPolicy) {
          this.$noty.info('Auto-submit at deadline is only available when all drafts are set to "Do not accept late".')
          this.$nextTick(() => {
            this.settings.autoSubmission = false
            this.settings.preventAfterDueDate = false
          })
          return
        }
        this.settings.preventAfterDueDate = true
      } else {
        this.settings.preventAfterDueDate = false
      }
    },

    // Drag and drop handling
    onMoveCallback (evt) {
      // Prevent moving the Final Submission
      if (evt.draggedContext.element.isFinal) {
        return false
      }
      // Prevent dropping after the Final Submission (it must stay at the end)
      if (evt.relatedContext.element && evt.relatedContext.element.isFinal && evt.draggedContext.futureIndex >= this.drafts.length - 1) {
        return false
      }
      return true
    },

    onDragEnd () {
      // Ensure Final Submission is always at the end
      const finalIndex = this.drafts.findIndex(d => d.isFinal)
      if (finalIndex !== -1 && finalIndex !== this.drafts.length - 1) {
        const finalDraft = this.drafts.splice(finalIndex, 1)[0]
        this.drafts.push(finalDraft)
      }
      // Reset collapsed state after reorder
      this.collapsedDrafts = []
    },

    // Error handling
    hasError (field) {
      return !!this.errors[field]
    },

    getError (field) {
      return this.errors[field] || ''
    },

    clearError (field) {
      if (this.errors[field]) {
        this.$delete(this.errors, field)
      }
    },

    getTabClass (tab) {
      const hasErrors = {
        drafts: this.hasDraftsTabErrors,
        submission: this.hasSubmissionTabErrors,
        analytics: this.hasAnalyticsTabErrors,
        files: this.hasFilesTabErrors
      }
      return hasErrors[tab] ? 'invalid-question-editor-tab-title' : 'question-editor-tab-title'
    },

    hasDraftErrors (draftIndex) {
      const errorKeys = Object.keys(this.errors)
      const prefix = `drafts.${draftIndex}.`
      return errorKeys.some(key => key.startsWith(prefix))
    },

    // Collapse/Expand
    toggleAllDrafts () {
      if (this.allDraftsCollapsed) {
        this.collapsedDrafts = []
      } else {
        this.collapsedDrafts = this.drafts.map((_, index) => index)
      }
      this.allDraftsCollapsed = !this.allDraftsCollapsed
    },

    toggleDraftCollapse (draftIndex) {
      const index = this.collapsedDrafts.indexOf(draftIndex)
      if (index > -1) {
        this.collapsedDrafts.splice(index, 1)
      } else {
        this.collapsedDrafts.push(draftIndex)
      }
      this.allDraftsCollapsed = this.collapsedDrafts.length === this.drafts.length
    },

    // Draft title helpers
    getDraftTitle (draft, draftIndex) {
      if (draft.title) {
        return draft.title
      }
      if (draft.isFinal) {
        return 'Final Submission'
      }
      // Count non-final drafts before this one to get the draft number
      let draftNumber = 0
      for (let i = 0; i <= draftIndex; i++) {
        if (!this.drafts[i].isFinal) {
          draftNumber++
        }
      }
      return `Draft ${draftNumber}`
    },

    getDraftPlaceholder (draftIndex) {
      const draft = this.drafts[draftIndex]
      if (draft && draft.isFinal) {
        return 'Final Submission'
      }
      // Count non-final drafts before this one to get the draft number
      let draftNumber = 0
      for (let i = 0; i <= draftIndex; i++) {
        if (!this.drafts[i].isFinal) {
          draftNumber++
        }
      }
      return `Draft ${draftNumber}`
    },

    getGroupName (assignTo, index) {
      if (assignTo.groups && assignTo.groups.length) {
        return assignTo.groups.map(g => g.text).join(', ')
      }
      return `Group ${index + 1}`
    },

    // Sync drafts with assignment assign_tos
    syncDraftAssignTos () {
      for (let draftIndex = 0; draftIndex < this.drafts.length; draftIndex++) {
        const draft = this.drafts[draftIndex]

        // Ensure draft has assign_tos array
        if (!draft.assign_tos) {
          draft.assign_tos = []
        }

        // Ensure draft has extensions array
        if (!draft.extensions) {
          draft.extensions = []
        }

        // Add missing assign_tos if assignment has more (no auto-populated dates)
        while (draft.assign_tos.length < this.assignTos.length) {
          const assignToIndex = draft.assign_tos.length
          const assignTo = this.assignTos[assignToIndex]

          draft.assign_tos.push({
            groups: assignTo.groups,
            available_from_date: '',
            available_from_time: '',
            due_date: '',
            due_time: '',
            final_submission_deadline_date: '',
            final_submission_deadline_time: ''
          })
        }

        // Remove extra assign_tos if assignment has fewer
        while (draft.assign_tos.length > this.assignTos.length) {
          draft.assign_tos.pop()
        }

        // Keep groups in sync
        for (let i = 0; i < draft.assign_tos.length; i++) {
          draft.assign_tos[i].groups = this.assignTos[i].groups
          // Initialize final submission deadline fields if missing
          if (typeof draft.assign_tos[i].final_submission_deadline_date === 'undefined') {
            draft.assign_tos[i].final_submission_deadline_date = ''
          }
          if (typeof draft.assign_tos[i].final_submission_deadline_time === 'undefined') {
            draft.assign_tos[i].final_submission_deadline_time = ''
          }
        }
      }
    },

    // Add a new draft (inserted before Final Submission)
    addDraft () {
      const newDraft = {
        uuid: this.generateUuid(),
        title: '',
        isFinal: false,
        late_policy: this.assignmentLatePolicy || '',
        late_deduction_percent: this.assignmentLateDeductionPercent || null,
        late_deduction_applied_once: this.assignmentLateDeductionAppliedOnce !== null ? this.assignmentLateDeductionAppliedOnce : null,
        late_deduction_application_period: this.assignmentLateDeductionApplicationPeriod || null,
        extensions: [],
        assign_tos: []
      }

      // Initialize assign_tos based on assignment's assign_tos (no auto-populated dates)
      for (let i = 0; i < this.assignTos.length; i++) {
        const assignTo = this.assignTos[i]
        newDraft.assign_tos.push({
          groups: assignTo.groups,
          available_from_date: '',
          available_from_time: '',
          due_date: '',
          due_time: '',
          final_submission_deadline_date: '',
          final_submission_deadline_time: ''
        })
      }

      // Find the Final Submission index and insert before it
      const finalIndex = this.drafts.findIndex(d => d.isFinal)
      if (finalIndex > -1) {
        this.drafts.splice(finalIndex, 0, newDraft)
      } else {
        // Should not happen, but fallback to pushing at end
        this.drafts.push(newDraft)
      }

      // Update collapsed indices for items after the new draft
      this.collapsedDrafts = this.collapsedDrafts.map(idx => idx >= finalIndex ? idx + 1 : idx)

      // Collapse previous drafts, keep new one expanded
      const newDraftIndex = finalIndex
      for (let i = 0; i < this.drafts.length; i++) {
        if (i !== newDraftIndex && !this.collapsedDrafts.includes(i)) {
          this.collapsedDrafts.push(i)
        }
      }

      // Adding a draft auto-unlocks the Final Submission (handled by watcher)
    },

    // Remove a draft (cannot remove Final Submission)
    async removeDraft (draftIndex) {
      // Set loading state
      this.isCheckingSubmissions = true
      this.draftToDeleteIndex = draftIndex
      const draft = this.drafts[draftIndex]
      if (draft.isFinal) {
        // Cannot remove Final Submission
        this.isCheckingSubmissions = false
        return
      }

      // If draft has no question_id yet (not saved), just remove from UI
      if (!draft.question_id) {
        this.removeLocalDraft(draftIndex)
        this.isCheckingSubmissions = false
        return
      }

      // Check for submissions before deleting
      try {
        const { data } = await axios.get(`/api/assignments/${this.assignmentId}/questions/${draft.question_id}/forge-draft-submissions`)

        if (data.type === 'error') {
          this.$noty.error(data.message)
          return
        }

        this.draftToDelete = draft
        this.draftSubmissionCount = data.submission_count || 0
        this.isDeleting = false
        this.$bvModal.show('modal-confirm-delete-draft')
      } catch (error) {
        this.$noty.error(error.message)
      } finally {
        this.isCheckingSubmissions = false
      }
    },

    async confirmDeleteDraft (bvModalEvent) {
      bvModalEvent.preventDefault()
      if (!this.draftToDelete || this.draftToDeleteIndex === null) {
        return
      }
      this.$emit('submitRemoveQuestion', this.draftToDelete.question_id)
      this.removeLocalDraft(this.draftToDeleteIndex)
      this.isDeleting = true
    },

    removeLocalDraft (draftIndex) {
      this.drafts.splice(draftIndex, 1)

      // Clear errors related to drafts
      const newErrors = {}
      for (const key in this.errors) {
        if (!key.startsWith('drafts.')) {
          newErrors[key] = this.errors[key]
        }
      }
      this.errors = newErrors

      // Update collapsed indices
      this.collapsedDrafts = this.collapsedDrafts
        .filter(idx => idx !== draftIndex)
        .map(idx => idx > draftIndex ? idx - 1 : idx)

      this.allDraftsCollapsed = this.drafts.length > 0 &&
        this.collapsedDrafts.length === this.drafts.length
    },

    // Save forge settings
    async saveForgeSettings () {
      this.isSaving = true
      this.errors = {}
      this.allFormErrors = []

      // Auto-generate titles for drafts without titles
      let draftNumber = 1
      for (let i = 0; i < this.drafts.length; i++) {
        const draft = this.drafts[i]
        if (!draft.title || !draft.title.trim()) {
          if (draft.isFinal) {
            draft.title = 'Final Submission'
          } else {
            draft.title = `Draft ${draftNumber}`
            draftNumber++
          }
        } else if (!draft.isFinal) {
          draftNumber++
        }
      }

      try {
        const { data } = await axios.patch(`/api/assignments/${this.assignmentId}/question/${this.questionId}/forge-settings`, {
          drafts: this.drafts,
          settings: this.settings,
          final_submission_locked: this.finalSubmissionLocked
        })

        if (data.type === 'error') {
          if (data.errors) {
            this.errors = data.errors
            this.allFormErrors = Object.values(data.errors)
            this.$bvModal.show('modal-form-errors-forge-settings')
          }
          this.$noty.error(data.message)
          return
        }

        // Update local drafts with question_ids from server response
        if (data.drafts) {
          this.drafts = data.drafts.map(serverDraft => {
            // Preserve local UI state while updating with server data
            const localDraft = this.drafts.find(d => d.uuid === serverDraft.uuid)
            return {
              ...localDraft,
              ...serverDraft,
              // Restore assign_tos with local date/time format if needed
              assign_tos: localDraft ? localDraft.assign_tos : serverDraft.assign_tos,
              // Preserve extensions
              extensions: localDraft ? localDraft.extensions : (serverDraft.extensions || [])
            }
          })
        }

        this.$noty.success(data.message || 'Forge settings saved successfully')
        this.$emit('settings-saved', { drafts: this.drafts, settings: this.settings })
        this.$bvModal.hide('modal-forge-settings')
        this.$emit('addDraft', this.currentPage)
      } catch (error) {
        if (error.response && error.response.status === 422) {
          const laravelErrors = error.response.data.errors
          const newErrors = {}
          const uniqueErrors = new Set()

          for (const key in laravelErrors) {
            const errorMessage = Array.isArray(laravelErrors[key])
              ? laravelErrors[key][0]
              : laravelErrors[key]

            newErrors[key] = errorMessage
            uniqueErrors.add(errorMessage)

            // Map combined 'due' errors to due_date field for display
            if (key.endsWith('.due')) {
              const basePath = key.replace('.due', '')
              newErrors[`${basePath}.due_date`] = errorMessage
            }
            // Map combined 'final_submission_deadline' errors
            if (key.endsWith('.final_submission_deadline')) {
              const basePath = key.replace('.final_submission_deadline', '')
              newErrors[`${basePath}.final_submission_deadline_date`] = errorMessage
            }
          }

          this.errors = newErrors
          this.allFormErrors = Array.from(uniqueErrors)
          this.$bvModal.show('modal-form-errors-forge-settings')
        } else {
          this.$noty.error(error.message)
        }
      } finally {
        this.isSaving = false
      }
    },

    // Extension methods
    async getAssignmentSummary () {
      try {
        const { data } = await axios.get(`/api/assignments/${this.assignmentId}/summary`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.courseId = data.assignment.course_id
        return true
      } catch (error) {
        this.$noty.error('Failed to load assignment summary')
        return false
      }
    },

    async loadEnrollments () {
      try {
        const { data } = await axios.get(`/api/enrollments/${this.courseId}/details`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return
        }
        this.enrollments = data.enrollments || []
      } catch (error) {
        this.$noty.error('Failed to load enrollments')
      }
    },

    getExtensions (draftIndex) {
      return this.drafts[draftIndex]?.extensions || []
    },

    getExtensionCount (draftIndex) {
      return this.getExtensions(draftIndex).length
    },

    getAvailableStudentsForExtension (draftIndex, currentExtIndex = null) {
      const existingExtensions = this.getExtensions(draftIndex)
      const existingUserIds = existingExtensions
        .map((ext, idx) => idx !== currentExtIndex ? ext.user_id : null)
        .filter(id => id !== null)

      return this.enrollments
        .filter(enrollment => !existingUserIds.includes(enrollment.id))
        .map(enrollment => ({
          user_id: enrollment.id,
          name: enrollment.name || `${enrollment.first_name || ''} ${enrollment.last_name || ''}`.trim() || 'Unknown Student'
        }))
    },

    addExtension (draftIndex) {
      const draft = this.drafts[draftIndex]

      // Initialize extensions array if not present
      if (!draft.extensions) {
        this.$set(draft, 'extensions', [])
      }

      // Pre-populate dates from the first assign_to if available
      const firstAssignTo = draft.assign_tos && draft.assign_tos.length ? draft.assign_tos[0] : null

      // Create new extension with pre-populated dates
      const newExtension = {
        user_id: null,
        student_name: '',
        due_date: firstAssignTo ? (firstAssignTo.due_date || '') : '',
        due_time: firstAssignTo ? (firstAssignTo.due_time || '') : '',
        final_submission_deadline_date: firstAssignTo ? (firstAssignTo.final_submission_deadline_date || '') : '',
        final_submission_deadline_time: firstAssignTo ? (firstAssignTo.final_submission_deadline_time || '') : ''
      }

      // Push to array and force update
      draft.extensions.push(newExtension)
      this.$forceUpdate()
    },

    onExtensionStudentSelect (draftIndex, extIndex, event) {
      const userId = parseInt(event.target.value, 10)

      if (isNaN(userId)) {
        return
      }

      // Set the user_id
      this.$set(this.drafts[draftIndex].extensions[extIndex], 'user_id', userId)

      // Update the student_name (enrollment uses 'id' not 'user_id')
      const enrollment = this.enrollments.find(e => e.id === userId)
      let studentName = ''
      if (enrollment) {
        studentName = enrollment.name || `${enrollment.first_name || ''} ${enrollment.last_name || ''}`.trim()
      }
      this.$set(this.drafts[draftIndex].extensions[extIndex], 'student_name', studentName)
      this.clearError(`drafts.${draftIndex}.extensions.${extIndex}.user_id`)
    },

    removeExtension (draftIndex, extIndex) {
      this.drafts[draftIndex].extensions.splice(extIndex, 1)
      // Clear any errors for this extension
      this.clearExtensionErrors(draftIndex, extIndex)
    },

    hasExtensionErrors (draftIndex, extIndex) {
      const prefix = `drafts.${draftIndex}.extensions.${extIndex}`
      return Object.keys(this.errors).some(key => key.startsWith(prefix))
    },

    getExtensionErrors (draftIndex, extIndex) {
      const prefix = `drafts.${draftIndex}.extensions.${extIndex}`
      const errors = []
      for (const key in this.errors) {
        if (key.startsWith(prefix)) {
          errors.push(this.errors[key])
        }
      }
      return errors
    },

    clearExtensionErrors (draftIndex, extIndex) {
      const prefix = `drafts.${draftIndex}.extensions.${extIndex}`
      const newErrors = {}
      for (const key in this.errors) {
        if (!key.startsWith(prefix)) {
          newErrors[key] = this.errors[key]
        }
      }
      this.errors = newErrors
    }
  }
}
</script>

<style scoped>
.datepicker {
  border-color: #8a8f90;
}

.drag-handle {
  cursor: grab;
  color: #6c757d;
}

.drag-handle:hover {
  color: #495057;
}

.drag-handle:active {
  cursor: grabbing;
}

.sortable-ghost {
  opacity: 0.5;
  background: #c8ebfb;
}

.sortable-drag {
  opacity: 1;
}

.lock-icon {
  font-size: 0.9rem;
  transition: color 0.2s ease;
}

.lock-icon-enabled:hover {
  color: #0056b3 !important;
}

.lock-icon-disabled {
  cursor: not-allowed;
}

.extensions-section {
  border-left: 3px solid #007bff !important;
}
</style>

<style>
#modal-forge-settings .invalid-question-editor-tab-title {
  background-color: #dc3545 !important;
  color: #fff !important;
  border-radius: 0.25rem;
}

#modal-forge-settings .invalid-question-editor-tab-title:hover {
  color: #fff !important;
}

#modal-forge-settings .nav-link.active.invalid-question-editor-tab-title {
  background-color: #dc3545 !important;
  color: #fff !important;
}

#modal-forge-settings .nav-link.active {
  background-color: #007bff !important;
  color: #fff !important;
  font-weight: bold;
}

#modal-forge-settings .nav-link.active:hover {
  color: #fff !important;
}
</style>

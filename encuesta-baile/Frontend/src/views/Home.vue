<template>
  <div class="min-h-screen bg-surface-base text-on-background flex flex-col justify-between selection:bg-secondary-base/20 selection:text-secondary-base">
    
    <!-- Main Container workspace -->
    <main class="flex-grow max-w-7xl w-full mx-auto px-6 md:px-10 py-10 md:py-14">
      <div class="space-y-12">
        
        <!-- Router View now handles the active screen based on URL -->
        <router-view
          :options="VOTE_OPTIONS"
          :selectedOptionId="selectedOptionId"
          :isSubmitting="isSubmitting"
          :submitText="submitText"
          :submitColor="submitColor"
          :votesA="votesA"
          :votesB="votesB"
          :userVote="userVote"
          @selectOption="handleSelectOption"
          @submitVote="handleSubmitVote"
          @resetVote="handleResetVote"
        />

      </div>
    </main>

    <!-- Dynamic dialog component -->
    <CivicModal
      :isOpen="modalOpen"
      :title="modalTitle"
      @close="modalOpen = false"
    >
      <p>{{ modalContent }}</p>
    </CivicModal>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import Swal from 'sweetalert2';
import CivicModal from './components/CivicModal.vue';
import { VOTE_OPTIONS } from '../data.js';

const router = useRouter();

// Voting states
const selectedOptionId = ref(null);
const hasVoted = ref(false);
const userVote = ref(null);

// Dynamic voting counts
const votesA = ref(315);
const votesB = ref(298);

// Loading indicator for submission
const isSubmitting = ref(false);
const submitText = ref('CONFIRMAR MI PREFERENCIA');
const submitColor = ref('bg-primary-base');

// Modal handlers
const modalOpen = ref(false);
const modalTitle = ref('');
const modalContent = ref('');

// Persist voting details in localStorage
onMounted(() => {
  const savedVote = localStorage.getItem('civic_survey_user_vote');
  const savedHasVoted = localStorage.getItem('civic_survey_has_voted') === 'true';  
  const savedVotesA = localStorage.getItem('civic_survey_votes_a');
  const savedVotesB = localStorage.getItem('civic_survey_votes_b');

  if (savedVote && savedHasVoted) {
    userVote.value = savedVote;
    hasVoted.value = true;
    // Auto-select user's current choice
    selectedOptionId.value = savedVote;
  }
  
  if (savedVotesA) votesA.value = parseInt(savedVotesA, 10);
  if (savedVotesB) votesB.value = parseInt(savedVotesB, 10);
});

// Save changes helper
const updateVotesRecord = (newHasVoted, voteId, finalVotesA, finalVotesB) => {
  hasVoted.value = newHasVoted;
  userVote.value = voteId;
  votesA.value = finalVotesA;
  votesB.value = finalVotesB;

  localStorage.setItem('civic_survey_has_voted', newHasVoted ? 'true' : 'false');
  if (voteId) {
    localStorage.setItem('civic_survey_user_vote', voteId);
  } else {
    localStorage.removeItem('civic_survey_user_vote');
  }
  localStorage.setItem('civic_survey_votes_a', finalVotesA.toString());
  localStorage.setItem('civic_survey_votes_b', finalVotesB.toString());
};

// Select an option with micro action
const handleSelectOption = (id) => {
  selectedOptionId.value = id;
};

// Submit vote with delay/spinner animation as in original code
const handleSubmitVote = () => {
  if (!selectedOptionId.value) return;

  isSubmitting.value = true;
  submitText.value = 'REGISTRANDO FIRMA DIGITAL...';

  // Simulate cryptographic hashing / network post
  setTimeout(() => {
    submitText.value = '✔ PREFERENCIA REGISTRADA EN EL PADRÓN';
    submitColor.value = 'bg-emerald-600';

    // Update counters
    const newVotesA = selectedOptionId.value === 'option-a' ? votesA.value + 1 : votesA.value;
    const newVotesB = selectedOptionId.value === 'option-b' ? votesB.value + 1 : votesB.value;
    
    updateVotesRecord(true, selectedOptionId.value, newVotesA, newVotesB);

    setTimeout(() => {
      isSubmitting.value = false;
      // Maintain success state or toggle visibility
      submitText.value = '✔ PREFERENCIA REGISTRADA EN EL PADRÓN';
      submitColor.value = 'bg-emerald-600';
      // Show SweetAlert success message instead of navigating
      Swal.fire({
        title: '¡Respuesta Guardada!',
        text: 'Tu selección se mandó correctamente.',
        icon: 'success',
        confirmButtonText: 'Aceptar',
        confirmButtonColor: '#10b981'
      });
    }, 1000);
  }, 1200);
};

// Reset vote to change preference
const handleResetVote = () => {
  if (!userVote.value) return;

  const newVotesA = userVote.value === 'option-a' ? Math.max(0, votesA.value - 1) : votesA.value;
  const newVotesB = userVote.value === 'option-b' ? Math.max(0, votesB.value - 1) : votesB.value;

  updateVotesRecord(false, null, newVotesA, newVotesB);
  selectedOptionId.value = null;
  router.push('/');
  
  // reset button style to default if switching back to survey
  submitText.value = 'CONFIRMAR MI PREFERENCIA';
  submitColor.value = 'bg-primary-base';
};
</script>

import React, { useState, useEffect } from 'react';
import { VOTE_OPTIONS } from './data';
import { ActiveScreen } from './types';
import CivicModal from './components/CivicModal';
import SondeoFormScreen from './screens/SondeoFormScreen';
import ResultsScreen from './screens/ResultsScreen';

export default function App() {
  // Navigation / views - Defaults directly to the Survey flow
  const [activeScreen, setActiveScreen] = useState<ActiveScreen>('survey');

  // Voting states
  const [selectedOptionId, setSelectedOptionId] = useState<string | null>(null);
  const [hasVoted, setHasVoted] = useState<boolean>(false);
  const [userVote, setUserVote] = useState<string | null>(null);

  // Dynamic voting counts
  const [votesA, setVotesA] = useState<number>(315);
  const [votesB, setVotesB] = useState<number>(298);

  // Loading indicator for submission
  const [isSubmitting, setIsSubmitting] = useState<boolean>(false);
  const [submitText, setSubmitText] = useState<string>('CONFIRMAR MI PREFERENCIA');
  const [submitColor, setSubmitColor] = useState<string>('bg-primary-base');

  // Modal handlers
  const [modalOpen, setModalOpen] = useState<boolean>(false);
  const [modalTitle, setModalTitle] = useState<string>('');
  const [modalContent, setModalContent] = useState<string>('');

  // Persist voting details in localStorage
  useEffect(() => {
    const savedVote = localStorage.getItem('civic_survey_user_vote');
    const savedHasVoted = localStorage.getItem('civic_survey_has_voted') === 'true';  
    const savedVotesA = localStorage.getItem('civic_survey_votes_a');
    const savedVotesB = localStorage.getItem('civic_survey_votes_b');

    if (savedVote && savedHasVoted) {
      setUserVote(savedVote);
      setHasVoted(true);
      // Auto-select user's current choice
      setSelectedOptionId(savedVote);
    }
    
    if (savedVotesA) setVotesA(parseInt(savedVotesA, 10));
    if (savedVotesB) setVotesB(parseInt(savedVotesB, 10));
  }, []);

  // Save changes helper
  const updateVotesRecord = (newHasVoted: boolean, voteId: string | null, finalVotesA: number, finalVotesB: number) => {
    setHasVoted(newHasVoted);
    setUserVote(voteId);
    setVotesA(finalVotesA);
    setVotesB(finalVotesB);

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
  const handleSelectOption = (id: string) => {
    setSelectedOptionId(id);
  };

  // Submit vote with delay/spinner animation as in original code
  const handleSubmitVote = () => {
    if (!selectedOptionId) return;

    setIsSubmitting(true);
    setSubmitText('REGISTRANDO FIRMA DIGITAL...');

    // Simulate cryptographic hashing / network post
    setTimeout(() => {
      setSubmitText('✔ PREFERENCIA REGISTRADA EN EL PADRÓN');
      setSubmitColor('bg-emerald-600');

      // Update counters
      const newVotesA = selectedOptionId === 'option-a' ? votesA + 1 : votesA;
      const newVotesB = selectedOptionId === 'option-b' ? votesB + 1 : votesB;
      
      updateVotesRecord(true, selectedOptionId, newVotesA, newVotesB);

      setTimeout(() => {
        setIsSubmitting(false);
        // Maintain success state or toggle visibility
        setSubmitText('✔ PREFERENCIA REGISTRADA EN EL PADRÓN');
        setSubmitColor('bg-emerald-600');
      }, 1000);
    }, 1200);
  };

  // Reset vote to change preference
  const handleResetVote = () => {
    if (!userVote) return;

    const newVotesA = userVote === 'option-a' ? Math.max(0, votesA - 1) : votesA;
    const newVotesB = userVote === 'option-b' ? Math.max(0, votesB - 1) : votesB;

    updateVotesRecord(false, null, newVotesA, newVotesB);
    setSelectedOptionId(null);
    setActiveScreen('survey');
  };

  return (
    <div className="min-h-screen bg-surface-base text-on-background flex flex-col justify-between selection:bg-secondary-base/20 selection:text-secondary-base">
      
      {/* Main Container workspace */}
      <main className="flex-grow max-w-7xl w-full mx-auto px-6 md:px-10 py-10 md:py-14">
        <div className="space-y-12">
          
          {/* Active Screen Rendering */}
          {activeScreen === 'survey' && (
            <SondeoFormScreen
              options={VOTE_OPTIONS}
              selectedOptionId={selectedOptionId}
              isSubmitting={isSubmitting}
              submitText={submitText}
              submitColor={submitColor}
              onSelectOption={handleSelectOption}
              onSubmitVote={handleSubmitVote}
            />
          )}

          {/* Results screen remains exactly here and functional, just in case you trigger it in state */}
          {activeScreen === 'results' && (
            <ResultsScreen
              votesA={votesA}
              votesB={votesB}
              userVote={userVote}
              onResetVote={handleResetVote}
            />
          )}

        </div>
      </main>

      {/* Dynamic dialog component */}
      <CivicModal
        isOpen={modalOpen}
        title={modalTitle}
        onClose={() => setModalOpen(false)}
      >
        <p>{modalContent}</p>
      </CivicModal>
    </div>
  );
}


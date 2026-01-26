/**
 * Spiral Tower Mad Libs
 * Handles Mad Libs functionality for floors with "Mad Libs Floor" in the title
 */
(function() {
    'use strict';

    // Check if this is a Mad Libs floor - run immediately to hide content ASAP
    const pageTitle = document.title || '';
    const isMadLibsFloor = pageTitle.toLowerCase().includes('mad libs floor');

    if (!isMadLibsFloor) {
        return;
    }

    // Inject CSS immediately to hide content before DOM is ready
    const style = document.createElement('style');
    style.textContent = `
        .spiral-tower-floor-content { display: none !important; }

        /* Mad Libs portals list container */
        .madlibs-portals-list {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            display: flex;
            flex-direction: column;
            gap: 4px;
            z-index: 50;
        }

        .madlibs-portals-list a {
            font-size: 12px;
            color: #fff;
            text-decoration: none;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.8);
            line-height: 1.2;
        }

        .madlibs-portals-list a:hover {
            text-decoration: underline;
        }

        .madlibs-container {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 90%;
            max-width: 500px;
            max-height: 80vh;
            padding: 1.5rem;
            background: rgba(0, 0, 0, 0.85);
            border-radius: 12px;
            backdrop-filter: blur(10px);
            overflow-y: auto;
            box-sizing: border-box;
            z-index: 100;
        }

        .madlibs-title {
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
            text-align: center;
            color: #fff;
        }

        .madlibs-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .madlibs-field {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
        }

        .madlibs-field label {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.8);
            text-transform: capitalize;
        }

        .madlibs-field input {
            padding: 0.75rem 1rem;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            font-size: 1rem;
            transition: border-color 0.2s, background 0.2s;
        }

        .madlibs-field input:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.5);
            background: rgba(255, 255, 255, 0.15);
        }

        .madlibs-submit {
            margin-top: 1rem;
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .madlibs-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
        }

        .madlibs-submit:active {
            transform: translateY(0);
        }

        .spiral-tower-floor-content.madlibs-revealed {
            display: block !important;
            animation: madlibsReveal 0.6s ease-out;
        }

        .spiral-tower-floor-content .madlibs-word {
            color: #ffd700;
            font-weight: 600;
        }

        @keyframes madlibsReveal {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    `;
    document.head.appendChild(style);

    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function() {
        reorganizeMadLibsPortals();
        initMadLibs();
    });

    // Reorganize Mad Libs portals as simple text links on the left
    function reorganizeMadLibsPortals() {
        // Find all portals that link to Mad Libs rooms by checking data-tooltip
        const allPortals = document.querySelectorAll('.floor-gizmo[data-tooltip*="Mad Libs Room"]');

        if (allPortals.length === 0) {
            return;
        }

        // Create a new container for the portal links
        const listContainer = document.createElement('div');
        listContainer.className = 'madlibs-portals-list';

        // Extract link info from each portal and create simple text links
        allPortals.forEach(portal => {
            const link = portal.querySelector('a');
            const tooltip = portal.getAttribute('data-tooltip');

            if (link && tooltip) {
                const newLink = document.createElement('a');
                newLink.href = link.href;
                newLink.textContent = tooltip;
                listContainer.appendChild(newLink);
            }

            // Hide the original portal
            portal.style.display = 'none';
        });

        // Add the list inside the floor wrapper so it scrolls with the background
        const floorWrapper = document.querySelector('.spiral-tower-floor-wrapper');
        if (floorWrapper) {
            floorWrapper.appendChild(listContainer);
        } else {
            document.body.appendChild(listContainer);
        }
    }

    function initMadLibs() {
        const contentDiv = document.querySelector('.spiral-tower-floor-content');

        if (!contentDiv) {
            console.warn('Mad Libs: Could not find .spiral-tower-floor-content');
            return;
        }

        // Store the original HTML content
        const originalContent = contentDiv.innerHTML;

        // Find all bracketed terms like [adjective], [noun], etc.
        const bracketPattern = /\[([^\]]+)\]/g;
        const matches = [];
        let match;

        while ((match = bracketPattern.exec(originalContent)) !== null) {
            matches.push({
                fullMatch: match[0],      // e.g., "[adjective]"
                term: match[1],           // e.g., "adjective"
                index: matches.length     // unique index for each occurrence
            });
        }

        if (matches.length === 0) {
            // No Mad Libs terms found, just show the content
            contentDiv.style.display = '';
            return;
        }

        // Create the Mad Libs form
        const container = document.createElement('div');
        container.className = 'madlibs-container';

        const title = document.createElement('h2');
        title.className = 'madlibs-title';
        title.textContent = 'Fill in the blanks!';
        container.appendChild(title);

        const form = document.createElement('form');
        form.className = 'madlibs-form';

        // Create an input for each bracketed term
        matches.forEach((item, index) => {
            const field = document.createElement('div');
            field.className = 'madlibs-field';

            const label = document.createElement('label');
            label.setAttribute('for', `madlibs-input-${index}`);
            label.textContent = `${index + 1}. ${item.term}`;

            const input = document.createElement('input');
            input.type = 'text';
            input.id = `madlibs-input-${index}`;
            input.name = `madlibs-${index}`;
            input.required = true;
            input.dataset.index = index;

            field.appendChild(label);
            field.appendChild(input);
            form.appendChild(field);
        });

        const submitBtn = document.createElement('button');
        submitBtn.type = 'submit';
        submitBtn.className = 'madlibs-submit';
        submitBtn.textContent = 'Reveal the Story!';
        form.appendChild(submitBtn);

        // Status message element
        const statusMsg = document.createElement('p');
        statusMsg.className = 'madlibs-status';
        statusMsg.style.cssText = 'text-align: center; margin-top: 1rem; color: #fff; display: none;';
        form.appendChild(statusMsg);

        container.appendChild(form);

        // Insert the form before the hidden content
        contentDiv.parentNode.insertBefore(container, contentDiv);

        // Handle form submission
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            // Collect all the user inputs
            const inputs = form.querySelectorAll('input[type="text"]');
            const userWords = [];

            inputs.forEach(input => {
                userWords.push(input.value.trim());
            });

            // Replace bracketed terms with user words
            let newContent = originalContent;
            let matchIndex = 0;

            newContent = newContent.replace(bracketPattern, function() {
                const word = userWords[matchIndex] || '';
                matchIndex++;
                return `<span class="madlibs-word">${escapeHtml(word)}</span>`;
            });

            // Add space between adjacent madlibs words (e.g., [adjective][noun])
            newContent = newContent.replace(/<\/span><span/g, '</span> <span');

            // Check if user is logged in and we have the necessary data
            if (typeof spiralTowerMadLibs !== 'undefined' && spiralTowerMadLibs.isLoggedIn) {
                // Show loading state
                submitBtn.disabled = true;
                submitBtn.textContent = 'Creating your room...';
                statusMsg.style.display = 'block';
                statusMsg.textContent = 'Generating image and creating room (this may take a minute)...';

                // Make AJAX call to create the room
                const formData = new FormData();
                formData.append('action', 'spiral_tower_create_madlibs_room');
                formData.append('nonce', spiralTowerMadLibs.nonce);
                formData.append('floor_id', spiralTowerMadLibs.floorId);
                formData.append('content', newContent);

                fetch(spiralTowerMadLibs.ajaxUrl, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success and redirect to new room
                        statusMsg.innerHTML = `Room created! Redirecting to <strong>${escapeHtml(data.data.room_title)}</strong>...`;

                        // Redirect to the new room after a short delay
                        setTimeout(() => {
                            window.location.href = data.data.room_url;
                        }, 1500);
                    } else {
                        // Show error but still reveal the content locally
                        statusMsg.style.color = '#ff6b6b';
                        statusMsg.textContent = 'Could not create room: ' + (data.data?.message || 'Unknown error');
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Reveal the Story!';

                        // Fall back to just showing content locally after 2 seconds
                        setTimeout(() => {
                            container.style.display = 'none';
                            contentDiv.innerHTML = newContent;
                            contentDiv.classList.add('madlibs-revealed');
                        }, 2000);
                    }
                })
                .catch(error => {
                    console.error('Mad Libs error:', error);
                    statusMsg.style.color = '#ff6b6b';
                    statusMsg.textContent = 'Network error. Showing story locally...';

                    // Fall back to showing content locally
                    setTimeout(() => {
                        container.style.display = 'none';
                        contentDiv.innerHTML = newContent;
                        contentDiv.classList.add('madlibs-revealed');
                    }, 1500);
                });
            } else {
                // User not logged in - just show content locally
                container.style.display = 'none';
                contentDiv.innerHTML = newContent;
                contentDiv.classList.add('madlibs-revealed');
            }
        });

        // Focus the first input
        const firstInput = form.querySelector('input');
        if (firstInput) {
            firstInput.focus();
        }
    }

    // Helper function to escape HTML entities
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
})();

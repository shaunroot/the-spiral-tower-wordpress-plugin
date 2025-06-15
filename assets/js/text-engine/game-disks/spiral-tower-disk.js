const spiralTowerDisk = () => ({
  roomId: 'entrance', // the ID of the room the player starts in
  rooms: [
    {
      id: 'entrance',
      name: 'The Spiral Tower Entrance',
      desc: `You stand before an impossibly tall tower that spirals into the clouds. The stone is iridescent, shifting between deep purple and midnight blue as you move. A massive oak door with intricate runes carved around its frame stands before you.
    
      The air hums with magic, and small wisps of light dance around the entrance. A brass **PLAQUE** is mounted beside the door.
      
      The door is slightly ajar, allowing entrance to the **FOYER** if you go **NORTH**.`,
      img: `
           /\\
          /  \\
         /    \\
        /______\\
        |      |
        |  []  |
        |______|
      `,
      items: [
        {
          name: 'plaque',
          desc: `The plaque reads:
          
          "Welcome, seeker, to the Spiral Tower of Archmage Zephyrian. 
          Those who reach the top shall be granted one wish.
          But beware, the tower tests both mind and spirit.
          Not all who enter shall ascend."`,
        },
        {
          name: ['door', 'oak door', 'rune door'],
          desc: `The heavy oak door is inscribed with runes that softly glow with a blue light. The runes seem to shift and change as you look at them, as if they're alive.`,
          onUse() {
            println(`The door is already ajar. You can simply go NORTH to enter.`);
          }
        },
        {
          name: ['wisps', 'light', 'dancing light'],
          desc: `Small orbs of blue-white light dance and float around the entrance. They seem almost playful, moving away when you reach for them, then darting back when you withdraw your hand.`,
        }
      ],
      exits: [
        { dir: ['north', 'foyer'], id: 'foyer' },
      ],
    },
    {
      id: 'foyer',
      name: 'The Grand Foyer',
      desc: `The circular foyer of the Spiral Tower stretches upward, revealing the tower's hollow core. A spiraling staircase hugs the wall, winding its way up into darkness. Crystal chandeliers hang at various heights, illuminating the space with a warm glow.
    
      A **FOUNTAIN** in the center of the room bubbles with water that seems to shine with its own inner light. A stone **GARGOYLE** perches on its rim, frozen in a permanent snarl.
      
      An elderly man in deep purple robes, the **KEEPER**, stands by a large **BOOK** on a pedestal.
      
      The exit to the **SOUTH** leads back outside. The staircase spirals **UP** to the next floor. A hallway extends to the **EAST**.`,
      items: [
        {
          name: 'fountain',
          desc: `The fountain is carved from a single piece of white marble. The water flowing from it glows with a soft blue light, casting rippling reflections on the ceiling. There are a few **COINS** at the bottom.`,
          onUse() {
            println(`You splash some of the glowing water on your face. It feels refreshing and energizing, clearing your mind. For a moment, the runes and magical inscriptions throughout the tower seem slightly clearer to you.`);
          },
        },
        {
          name: 'coins',
          desc: `Several coins glint at the bottom of the fountain. Most appear to be copper or silver, but one **GOLD COIN** catches your eye.`,
          onLook() {
            const room = getRoom('foyer');
            const hasCoin = getItemInInventory('gold coin');

            if (!hasCoin && !room.coinTaken) {
              println(`You see a particularly shiny **GOLD COIN** among the other coins at the bottom of the fountain.`);

              room.items.push({
                name: ['gold coin', 'coin'],
                desc: `A perfectly preserved gold coin. One side bears the image of the Spiral Tower, the other the face of a bearded wizard who must be Archmage Zephyrian.`,
                isTakeable: true,
                onTake() {
                  println(`You reach into the fountain and retrieve the gold coin. As your fingers close around it, it grows momentarily warm, then cool again.`);
                  room.coinTaken = true;
                },
                onUse() {
                  if (disk.roomId === 'moonlight_chamber') {
                    const moonlightRoom = getRoom('moonlight_chamber');
                    if (!moonlightRoom.lunarMirrorUnlocked) {
                      println(`You place the gold coin in the stone basin. It fits perfectly into a circular indentation you hadn't noticed before. There's a soft click, and the **LUNAR MIRROR** slides aside, revealing a hidden **ALCOVE** to the **NORTH**.`);
                      moonlightRoom.exits.push({ dir: 'north', id: 'arcane_library' });
                      moonlightRoom.lunarMirrorUnlocked = true;
                      disk.inventory = disk.inventory.filter(item => item.name.includes('gold coin') === false);
                    } else {
                      println(`You've already used the coin to unlock the passage.`);
                    }
                  } else if (disk.roomId === 'illusion_gallery') {
                    println(`As you hold up the gold coin in the Illusion Gallery, the reflections multiply it into seemingly thousands of identical coins floating through the air. The effect is dizzying but beautiful.`);
                  } else {
                    println(`You flip the gold coin. It catches the light beautifully as it spins through the air, landing in your palm.`);
                  }
                }
              });
            }
          }
        },
        {
          name: 'gargoyle',
          desc: `The stone gargoyle is about two feet tall with bat-like wings folded against its back. Its face is locked in a fearsome snarl, water trickling from its open mouth into the fountain. Its eyes seem to follow you as you move.`,
          onLook() {
            if (!disk.gargoyleAnimated) {
              println(`As you examine the gargoyle, you could swear its head turns slightly to keep its gaze on you. But that must be a trick of the light... right?`);
            }
          },
          onUse() {
            if (!disk.gargoyleAnimated) {
              println(`As your fingers touch the cold stone of the gargoyle, its eyes suddenly glow red and it shakes itself, stone scraping against stone! It stretches its wings and turns to face you directly.
              
              "Finally, someone with the sense to say hello properly," it growls in a gravelly voice. "Five hundred years I've been sitting here, and you're the first to actually touch me instead of just gawking."
              
              The gargoyle settles back into position but remains clearly animate now, its stone eyes blinking occasionally.
              
              "The name's Grimbald. I was bound here by Zephyrian to guard the tower and offer... guidance, I suppose. Though I'm selective about who gets it. I like you though. You can ask me about the **TOWER** or the **ARCHMAGE** if you want."
              `);

              disk.gargoyleAnimated = true;

              // Add Grimbald as a character now that he's animated
              disk.characters.push({
                name: ['Grimbald', 'gargoyle'],
                roomId: 'foyer',
                desc: `The once-stone gargoyle is now clearly alive, occasionally shifting position or scratching itself with a clawed foot. Its red eyes glow with intelligence and perhaps a hint of mischief.`,
                onTalk: () => println(`"What do you want to know?" Grimbald grumbles, his voice like stones grinding together.`),
                topics: [
                  {
                    option: `Tell me about the **TOWER**.`,
                    line: `"The Spiral Tower has stood for over a millennium. Archmage Zephyrian built it as both a residence and a test. Each floor has a different theme—elemental, illusory, temporal, and so on. The higher you go, the more dangerous and complex the challenges become."
                    
                    Grimbald stretches his wings. "But it's the spaces between floors you should watch for. Hidden rooms, secret passages. That's where the real treasures are. Like that hallway to the east—not part of the main tower structure at all, but a space Zephyrian folded in between dimensions."`,
                  },
                  {
                    option: `Tell me about the **ARCHMAGE**.`,
                    line: `"Zephyrian..." The gargoyle's eyes dim slightly. "Brilliant but complicated. Created me and my kin to guard his tower, then vanished to who knows where. Some say he ascended to another plane, others that he's sleeping at the very top of the tower. The Keeper thinks he'll return someday, but he's been saying that for centuries."
                    
                    Grimbald lowers his voice to a whisper. "Between you and me, there are letters from Zephyrian hidden throughout the tower. Personal correspondences that might reveal where he went. I know of one behind the **BOOK** on the pedestal, but don't tell the Keeper I told you."`,
                  },
                  {
                    option: `What can you tell me about the **KEEPER**?`,
                    line: `"Ah, Thaddeus. Been here almost as long as I have, though he's human—or was. I think the tower's magic has preserved him well beyond his natural years. He's dedicated to maintaining the tower and its traditions."
                    
                    The gargoyle snorts, a small puff of dust escaping his nostrils. "He knows a great deal about the tower's workings, but he's bound by oath to only help visitors in certain ways. And he takes that oath very seriously. Still, he's not a bad sort, for a human."`,
                  },
                  {
                    option: `Do you know how to reach the **TOP**?`,
                    line: `Grimbald lets out a raspy laugh. "If I told you that, it would defeat the purpose of the tower, wouldn't it? The journey is different for everyone. The tower... reshapes itself based on the visitor."
                    
                    He leans forward conspiratorially. "But I will say this—pay attention to patterns. Magic leaves traces, and if you can see the pattern in those traces, you can sometimes predict what comes next. Also, not all challenges are meant to be overcome through direct confrontation. Sometimes observation and patience are the better tools."`,
                  },
                ],
              });
            } else {
              println(`Grimbald is already animated. You can TALK to him now.`);
            }
          }
        },
        {
          name: ['book', 'pedestal book', 'visitor book'],
          desc: `A massive leather-bound tome rests open on a stone pedestal. Its pages appear to be filled with names, written in many different hands and in various colors of ink.`,
          onUse() {
            println(`You flip through the book's pages. It seems to be a visitor's registry, with names dating back centuries. Some entries are accompanied by notes about how far the visitor managed to ascend the tower. Very few appear to have reached the top.
            
            The last page has space for a new entry. There's a **QUILL** resting in an inkwell beside the book.`);

            if (!disk.inventory.some(item => item.name.includes('letter'))) {
              println(`As you turn the pages, you notice something caught between the book and the pedestal—the corner of a yellowed **LETTER** is just barely visible.`);
            }
          }
        },
        {
          name: 'quill',
          desc: `A magnificent feather quill, seemingly taken from some exotic bird. Its plume shifts colors in the light, from deep blue to purple to crimson.`,
          onUse() {
            if (disk.roomId === 'foyer') {
              println(`You take the quill and sign your name in the visitor's book. As the ink dries, it begins to shimmer slightly. The Keeper nods approvingly.
              
              "Now you are officially a Seeker of the Tower," he says. "May your journey bring you wisdom."`);
              disk.playerRegistered = true;
            } else {
              println(`You might need something to write on for this to be useful.`);
            }
          },
          isTakeable: true,
          onTake() {
            const keeper = getCharacter('Keeper');
            if (keeper) {
              println(`As you reach for the quill, the Keeper clears his throat.
              
              "That quill belongs with the registry," he says firmly. "You may use it to sign, but it must remain here for other visitors."
              
              You withdraw your hand.`);
              return false; // Prevent taking the quill
            } else {
              println(`You take the exotic feather quill. It feels unusually light in your hand.`);
            }
          }
        },
        {
          name: ['letter', 'parchment', 'hidden letter'],
          desc: `A yellowed letter written in an elegant, flowing script. It reads:
          
          "My dearest Elara,
          
          The construction of the Moonlight Chamber is complete at last. I've connected it to the basic elemental rooms as we discussed. The lunar mirror functions perfectly - your enchantment work is as brilliant as ever. I've left a token that will grant access to my private library, should you wish to visit when I'm deep in my studies.
          
          I miss our conversations. The tower grows ever taller, but without you to share in its wonders, each new marvel feels somehow diminished.
          
          With enduring affection,
          Z."`,
          isHidden: true,
          isTakeable: true,
          onTake() {
            println(`You carefully extract the letter from behind the book without the Keeper noticing. It feels warm to the touch, as if it had been written recently, though the parchment is clearly aged.`);
          }
        }
      ],
      exits: [
        { dir: 'south', id: 'entrance' },
        { dir: 'up', id: 'element_hall' },
        { dir: 'east', id: 'whispering_corridor' }
      ],
      onLook() {
        const room = getRoom('foyer');
        if (room.letterRevealed) {
          return;
        }

        const book = room.items.find(item => item.name.includes('book'));
        if (book && book.onUse) {
          const letter = room.items.find(item => item.name.includes('letter'));
          if (letter && letter.isHidden) {
            letter.isHidden = false;
            room.letterRevealed = true;
          }
        }
      }
    },
    {
      id: 'element_hall',
      name: 'Elemental Hall',
      desc: `The stairway opens onto a circular chamber with four doors, each bearing a different elemental symbol: a flickering **FLAME**, a crashing **WAVE**, a swirling **CLOUD**, and a verdant **LEAF**.
  
      In the center of the room stands a circular stone **DAIS** with four small indentations arranged in a diamond pattern.
        
      The **STAIRS** continue upward, and also lead back **DOWN** to the foyer.`,
      items: [
        {
          name: ['dais', 'stone dais', 'platform'],
          desc: `The stone dais is about waist-high and perfectly circular. Its surface contains four shallow, symbol-shaped indentations: a flame, a wave, a cloud, and a leaf. They seem designed to hold correspondingly shaped objects.`,
          onUse() {
            const hasAllTokens = disk.inventory.some(item => item.name.includes('fire token')) &&
              disk.inventory.some(item => item.name.includes('water token')) &&
              disk.inventory.some(item => item.name.includes('air token')) &&
              disk.inventory.some(item => item.name.includes('earth token'));
        
            if (hasAllTokens) {
              println(`You place each elemental token in its corresponding indentation. As the final token clicks into place, they all begin to glow brightly. The dais slowly rotates, and a fifth doorway shimmers into existence on the wall—a doorway marked with all four elemental symbols combined.
        
              The doorway leads **NORTHWEST** to a previously hidden chamber.
              
              Additionally, you hear a deep harmonic resonance throughout the tower, and the magical barrier blocking the upward staircase dissolves!`);
        
              const room = getRoom('element_hall');
              
              // Add the moonlight chamber exit
              if (!room.exits.find(exit => exit.dir === 'northwest')) {
                room.exits.push({ dir: 'northwest', id: 'moonlight_chamber' });
              }
        
              // Remove the block from the upward exit
              const upExit = room.exits.find(exit => exit.dir === 'up');
              if (upExit && upExit.block) {
                delete upExit.block;
              }
        
              // Remove tokens from inventory
              disk.inventory = disk.inventory.filter(item =>
                !item.name.includes('fire token') &&
                !item.name.includes('water token') &&
                !item.name.includes('air token') &&
                !item.name.includes('earth token')
              );
              
              // Set flag to remember this has been done
              disk.elementalPuzzleSolved = true;
              
            } else {
              const tokenCount = ['fire token', 'water token', 'air token', 'earth token']
                .filter(token => disk.inventory.some(item => item.name.includes(token))).length;
              
              if (tokenCount === 0) {
                println(`The indentations in the dais seem to be waiting for something to be placed in them. You sense they require items from the four elemental chambers.`);
              } else {
                println(`You have ${tokenCount} of the 4 required elemental tokens. You need to collect tokens from all four elemental chambers before the dais will activate.`);
              }
            }
          }
        },
        {
          name: ['flame', 'flame door', 'fire door'],
          desc: `A door of deep red wood with a flame symbol that seems to flicker with actual fire, though it doesn't burn.`,
          onUse() {
            goDir('north');
          }
        },
        {
          name: ['wave', 'wave door', 'water door'],
          desc: `A door that appears to be made of blue-green glass, with a wave symbol that subtly ripples as if it were real water.`,
          onUse() {
            goDir('east');
          }
        },
        {
          name: ['cloud', 'cloud door', 'air door'],
          desc: `A door of light gray wood that seems almost translucent, with a cloud symbol that gently swirls with actual movement.`,
          onUse() {
            goDir('south');
          }
        },
        {
          name: ['leaf', 'leaf door', 'earth door'],
          desc: `A door of rich brown wood with intricate grain patterns, bearing a leaf symbol that looks fresh and alive, as if recently plucked from a tree.`,
          onUse() {
            goDir('west');
          }
        }
      ],
      exits: [
        { dir: 'north', id: 'fire_chamber' },
        { dir: 'east', id: 'water_chamber' },
        { dir: 'south', id: 'air_chamber' },
        { dir: 'west', id: 'earth_chamber' },
        { dir: 'down', id: 'foyer' },
        { 
          dir: 'up', 
          id: 'illusion_gallery',
          block: `A shimmering magical barrier blocks the upward staircase. Ancient runes around the barrier glow softly, and you sense that it requires the completion of some elemental harmony to pass. The dais in the center of the room seems to be the key.`
        }
      ],
      onEnter() {
        // Check if puzzle was already solved
        if (disk.elementalPuzzleSolved) {
          const room = getRoom('element_hall');
          const upExit = room.exits.find(exit => exit.dir === 'up');
          if (upExit && upExit.block) {
            delete upExit.block;
            println(`The magical barrier remains dissolved from when you solved the elemental puzzle.`);
          }
        }
      }      
    },
    {
      id: 'fire_chamber',
      name: 'Chamber of Dancing Flames',
      desc: `This room is illuminated entirely by flames that dance around the walls in hypnotic patterns, remarkably giving off heat but no smoke. The floor is tiled with red and orange mosaic that seems to shift like embers.
      
      In the center of the room, a perpetual **BONFIRE** burns within a ring of black stones. Above it hovers a glowing red **CRYSTAL**. The crystal focuses its light to an **ICE WAND** on an ornate stand.
      
      The temperature is intense but not unbearable. The door back to the Elemental Hall lies to the **SOUTH**.`,
      items: [
        {
          name: ['bonfire', 'fire', 'flames'],
          desc: `The bonfire burns with flames of red, orange, and occasional flashes of blue. Despite its size, it produces no smoke, and the heat, while substantial, doesn't seem to be consuming any fuel.`,
          onUse() {
            println(`You cautiously extend your hands toward the fire. The heat is intense, but strangely, it doesn't burn you. You feel energized, as if the fire's energy is flowing into you.`);
          }
        },
        {
          name: ['crystal', 'red crystal', 'floating crystal'],
          desc: `A ruby-red crystal about the size of your fist hovers above the bonfire, rotating slowly. Flares of energy occasionally connect it to the flames below.`,
          isTakeable: false,
          onTake() {
            println(`As you reach for the crystal, the flames of the bonfire surge upward defensively. You pull your hand back quickly to avoid getting burned.
            
            There must be another way to get that crystal.`);
          },
          onUse() {
            if (disk.inventory.some(item => item.name.includes('ice wand'))) {
              println(`You point the ice wand at the bonfire. A beam of frost shoots forth, temporarily dampening the flames. In that moment, the red crystal drops a few inches, and you quickly snatch a small **FIRE TOKEN** that was hidden within it.
              
              The flames quickly recover, and the crystal returns to its original position.`);

              disk.inventory.push({
                name: ['fire token', 'flame token', 'red token'],
                desc: `A small token made of a warm, ruby-like material carved in the shape of a flame. It pulses slightly with inner heat.`,
                onUse() {
                  if (disk.roomId === 'element_hall') {
                    println(`You could place this on the dais with any other elemental tokens you've found.`);
                  } else {
                    println(`You roll the fire token between your fingers. It's pleasantly warm to the touch.`);
                  }
                }
              });
            } else {
              println(`The crystal is too high and too hot to reach directly. Perhaps you need something to counteract the heat.`);
            }
          }
        },
        {
          name: ['ice wand', 'silver wand', 'frost wand'],
          desc: `A slender silver wand with frost patterns etched along its length sits in a heat-resistant holder near the edge of the room, seemingly placed there to control the flames if needed.`,
          isTakeable: true,
          onTake() {
            println(`You take the ice wand. It feels surprisingly cold despite the heat of the chamber.`);
          },
          onUse() {
            if (disk.roomId === 'fire_chamber') {
              println(`You point the ice wand at the bonfire. A beam of frost shoots forth, temporarily dampening the flames. In that moment, the red crystal drops a few inches, and you quickly snatch a small **FIRE TOKEN** that was hidden within it.`);

              if (!disk.inventory.some(item => item.name.includes('fire token'))) {
                disk.inventory.push({
                  name: ['fire token', 'flame token', 'red token'],
                  desc: `A small token made of a warm, ruby-like material carved in the shape of a flame. It pulses slightly with inner heat.`,
                  onUse() {
                    if (disk.roomId === 'element_hall') {
                      println(`You could place this on the dais with any other elemental tokens you've found.`);
                    } else {
                      println(`You roll the fire token between your fingers. It's pleasantly warm to the touch.`);
                    }
                  }
                });
              }
            } else {
              println(`You wave the ice wand gently. The air around you cools momentarily, and a few snowflakes materialize before melting away.`);
            }
          }
        }
      ],
      exits: [
        { dir: 'south', id: 'element_hall' },
      ]
    },
    {
      id: 'water_chamber',
      name: 'Chamber of Flowing Waters',
      desc: `Cool blue light fills this chamber, which features waterways crossing the floor in intricate patterns. The walls appear to be made of flowing water somehow held in place, casting rippling reflections throughout the room.
      
      In the center, a circular **POOL** descends to unknown depths, its surface occasionally rippling as if something moves below. A blue **CRYSTAL** hovers a few inches above the water's surface.
      
      The air is cool and moist. Water droplets occasionally form and fall from the ceiling like gentle rain.
      
      The door back to the Elemental Hall lies to the **WEST**.`,
      items: [
        {
          name: ['pool', 'water pool', 'deep pool'],
          desc: `The circular pool is about ten feet across and appears to be very deep—you can't see the bottom. The water is impossibly clear yet deeply blue, and occasionally shimmers with bioluminescent particles. There is a **FISHING ROD** beneath the water.`,
          onUse() {
            println(`You dip your hand into the pool. The water is cool and invigorating. As you withdraw your hand, the droplets falling from your fingers seem to linger in the air slightly longer than they should, as if time moves differently in this chamber.`);
          }
        },
        {
          name: ['crystal', 'blue crystal', 'hovering crystal'],
          desc: `A sapphire-blue crystal about the size of your fist hovers above the pool's center, rotating slowly and casting blue reflections across the chamber.`,
          isTakeable: false,
          onTake() {
            println(`You reach for the crystal, but it's too far from the edge of the pool to reach. You could try wading in, but something tells you that would be unwise—the pool may be far deeper than it appears.`);
          },
          onUse() {
            if (disk.inventory.some(item => item.name.includes('fishing rod'))) {
              println(`You cast the fishing line toward the blue crystal. With careful aim, you manage to hook something near it—a small **WATER TOKEN** that was hidden beneath. You reel it in successfully!
              
              The crystal continues to hover undisturbed.`);

              disk.inventory.push({
                name: ['water token', 'wave token', 'blue token'],
                desc: `A small token made of a cool, sapphire-like material carved in the shape of a cresting wave. It feels slightly damp to the touch.`,
                onUse() {
                  if (disk.roomId === 'element_hall') {
                    println(`You could place this on the dais with any other elemental tokens you've found.`);
                  } else if (disk.roomId === 'desert_puzzle') {
                    println(`You hold up the water token, and it emits a fine mist that temporarily reveals a safe path through the sand. You quickly note the pattern before the mist evaporates.`);
                    const desert = getRoom('desert_puzzle');
                    desert.pathRevealed = true;
                  } else {
                    println(`You roll the water token between your fingers. It's pleasantly cool to the touch.`);
                  }
                }
              });
            } else {
              println(`The crystal is too far from the edge to reach. You might need something that can extend your reach.`);
            }
          }
        },
        // Add to the Water Chamber's items array:
        {
          name: ['fishing rod', 'rod', 'fishing pole'],
          desc: `A delicate fishing rod made of an unusual blue-green material leans against the wall near the pool, as if someone left it behind.`,
          isTakeable: true,
          onTake() {
            println(`You take the fishing rod. The line is nearly invisible, like a strand of pure water.`);
          },
          onUse() {
            if (disk.roomId === 'water_chamber') {
              println(`You cast the fishing line toward the blue crystal. With careful aim, you manage to hook something near it—a small **WATER TOKEN** that was hidden beneath. You reel it in successfully!`);

              if (!disk.inventory.some(item => item.name.includes('water token'))) {
                disk.inventory.push({
                  name: ['water token', 'wave token', 'blue token'],
                  desc: `A small token made of a cool, sapphire-like material carved in the shape of a cresting wave. It feels slightly damp to the touch.`,
                  onUse() {
                    if (disk.roomId === 'element_hall') {
                      println(`You could place this on the dais with any other elemental tokens you've found.`);
                    } else {
                      println(`You roll the water token between your fingers. It's pleasantly cool to the touch.`);
                    }
                  }
                });
              }
            } else {
              println(`You flick the fishing rod. The line extends much further than seems possible before retracting back when you will it.`);
            }
          }
        }
      ],
      exits: [
        { dir: 'west', id: 'element_hall' },
      ]
    },
    {
      id: 'air_chamber',
      name: 'Chamber of Swirling Winds',
      desc: `This airy chamber seems much larger inside than should be possible. The ceiling is lost in swirling mists high above. Gentle breezes constantly circulate, carrying the scent of fresh mountain air.
      
      Floating **PLATFORMS** of various sizes hover at different heights throughout the room, slowly drifting in circular patterns. A white **CRYSTAL** hovers at the very center of the chamber, far from any of the platforms.
      
      The door back to the Elemental Hall lies to the **NORTH**.`,
      items: [
        {
          name: ['platforms', 'floating platforms', 'hovering stones'],
          desc: `Flat, circular platforms of white stone hover at various heights throughout the chamber. They appear solid enough to stand on, though they drift slowly in consistent patterns.`,
          onUse() {
            println(`You step carefully onto the nearest platform. It dips slightly under your weight but holds firm. As you stand on it, you realize you can influence its movement slightly by shifting your weight, though its general pattern remains fixed.`);
          }
        },
        {
          name: ['crystal', 'white crystal', 'air crystal'],
          desc: `A clear crystal with swirling white mists inside hovers at the center of the chamber, rotating slowly. It's quite far from any of the floating platforms—there's no obvious way to reach it.`,
          isTakeable: false,
          onTake() {
            println(`The crystal is too far from any of the platforms to reach, and there's no obvious way to cross the open air to get to it.`);
          },
          onUse() {
            if (disk.inventory.some(item => item.name.includes('feather'))) {
              println(`You hold out the phoenix feather toward the white crystal. The feather glows brightly, and suddenly the air currents in the room bend to your will! You direct a gust of wind toward the crystal, dislodging a small **AIR TOKEN** that was hidden within it. The token floats directly to your outstretched hand.
              
              As soon as you grasp the token, the air currents return to their normal patterns.`);

              disk.inventory.push({
                name: ['air token', 'cloud token', 'white token'],
                desc: `A small token made of a lightweight, opal-like material carved in the shape of a swirling cloud. It feels almost weightless.`,
                onUse() {
                  if (disk.roomId === 'element_hall') {
                    println(`You could place this on the dais with any other elemental tokens you've found.`);
                  } else if (disk.roomId === 'chasm_bridge') {
                    println(`You hold up the air token. It glows softly, and the violent winds in the chasm calm momentarily, allowing you to cross safely.`);
                    goDir('north');
                  } else {
                    println(`You release the air token from your palm, and it hovers an inch above your hand before settling back down.`);
                  }
                }
              });
            } else {
              println(`The crystal is too far away to reach. Perhaps you need something that can influence the air currents in this chamber.`);
            }
          }
        }
      ],
      exits: [
        { dir: 'north', id: 'element_hall' },
      ]
    },
    {
      id: 'earth_chamber',
      name: 'Chamber of Living Earth',
      desc: `This chamber feels like stepping into an underground grotto. The walls are lined with glittering geodes and crystal formations. Bioluminescent **MUSHROOMS** provide soft, green illumination.
      
      The floor is covered with rich soil from which small plants and flowers sprout, growing visibly before your eyes and then returning to the earth in an endless cycle.
            
      At the center stands a twisted **TREE** that appears to be made of living crystal. A green **CRYSTAL** is embedded in its trunk.
            
      The door back to the Elemental Hall lies to the **EAST**.`,
      items: [
        {
          name: ['tree', 'crystal tree', 'living crystal'],
          desc: `The tree stands about fifteen feet tall with branches that chime softly when they touch each other. Its trunk and branches appear to be made of living crystal that shifts between emerald green and earthy brown. Leaves of thin crystal sprout, grow, and dissolve in a continuous cycle.`,
          onUse() {
            println(`You touch the trunk of the crystal tree. It feels warm and vibrates slightly under your touch, almost like a pulse. For a moment, you feel deeply connected to the earth beneath your feet.`);
          }
        },
        {
          name: ['crystal', 'green crystal', 'embedded crystal'],
          desc: `An emerald-green crystal about the size of your fist is embedded in the tree's trunk, pulsing with inner light that spreads through the veins of the tree itself.`,
          isTakeable: false,
          onTake() {
            println(`You try to pull the crystal from the tree trunk, but it's firmly embedded. The tree's branches shake agitatedly as you touch the crystal, and you sense it would be unwise to persist.`);
          },
          onUse() {
            if (disk.inventory.some(item => item.name.includes('garden spade'))) {
              println(`You carefully dig at the base of the crystal tree with the garden spade. After a few moments, you unearth a small **EARTH TOKEN** that was buried among the roots.
              
              The token seems to glow briefly as you lift it from the soil.`);

              disk.inventory.push({
                name: ['earth token', 'leaf token', 'green token'],
                desc: `A small token made of a deep emerald-like material carved in the shape of a leaf. It feels pleasantly heavy and cool in your palm.`,
                onUse() {
                  if (disk.roomId === 'element_hall') {
                    println(`You could place this on the dais with any other elemental tokens you've found.`);
                  } else if (disk.roomId === 'crumbling_passage') {
                    println(`You press the earth token against the unstable wall. The stone strengthens and stabilizes, making the passage safe to traverse.`);
                    const passage = getRoom('crumbling_passage');
                    passage.stabilized = true;
                  } else {
                    println(`You roll the earth token between your fingers. It has a pleasant weight to it, reminiscent of rich soil.`);
                  }
                }
              });
            } else {
              println(`You examine the crystal and the tree trunk carefully, but see no way to extract it. However, you notice the soil around the base of the tree seems unusually loose, as if something might be buried there.`);
            }
          }
        },
        {
          name: ['mushrooms', 'glowing mushrooms', 'fungi'],
          desc: `Clusters of bioluminescent mushrooms grow from the soil and along the walls, providing a gentle green-blue light. They seem to grow, mature, and decompose in accelerated time, their entire lifecycle visible to the naked eye.`,
          isTakeable: true,
          onTake() {
            println(`You carefully pick a few of the glowing mushrooms. They continue to glow softly in your hand.`);
          },
          onUse() {
            if (disk.roomId === 'dark_passage') {
              println(`You hold up the glowing mushrooms, which provide enough light to navigate the dark passage safely. You can now see the correct path forward.`);
              goDir('north');
            } else {
              println(`The mushrooms provide a soft, pleasant light.`);
            }
          }
        }
      ],
      exits: [
        { dir: 'east', id: 'element_hall' },
      ]
    },
    {
      id: 'moonlight_chamber',
      name: 'The Moonlight Chamber',
      desc: `This circular chamber seems to exist in perpetual night. The domed ceiling is a perfect replica of a starry sky, with constellations slowly shifting across its surface. A large **LUNAR MIRROR** dominates one wall, reflecting a full moon that isn't actually present in the room.
      
      Silver-blue light bathes everything in an ethereal glow. A stone **BASIN** sits directly under the center of the dome, catching moonlight that somehow streams down from the artificial sky. You see a piece of crystal circled with ornate silve inscriptions. The only word you recognize is the word truth. Could this be the fabled **TRUTH MONOCLE**?
      
      The chamber has a peaceful, timeless quality. It feels like a place between worlds.
      
      The exit back to the Elemental Hall lies to the **SOUTHEAST**.`,
      items: [
        {
          name: ['lunar mirror', 'mirror', 'moon mirror'],
          desc: `The large, circular mirror is framed in silver inscribed with lunar phases. Instead of reflecting the room, it shows a full moon set against a night sky. The image is so realistic you almost feel you could reach through into that night world.`,
          onUse() {
            println(`You touch the surface of the mirror. It ripples slightly under your fingertips, like disturbed water, then settles back to showing the full moon.`);
          }
        },
        {
          name: ['basin', 'stone basin', 'moonlight basin'],
          desc: `A circular basin carved from pale stone sits in the center of the room. Moonlight from the domed ceiling collects in it, pooling like liquid silver. There's a circular indentation at the basin's center that looks like it might hold something the size of a coin.`,
          onUse() {
            if (disk.inventory.some(item => item.name.includes('gold coin'))) {
              println(`The basin has a circular indentation that might fit the gold coin you have.`);
            } else {
              println(`The basin collects moonlight like water. The circular indentation at its center seems to be waiting for something specific to be placed there.`);
            }
          }
        },
        {
          name: ['truth monocle', 'monocle'],
          desc: `A small crystal monocle rests on the edge of the basin, glinting in the silver-blue light. It seems designed to reveal hidden truths.`,
          isTakeable: true,
          onTake() {
            println(`You pick up the crystal monocle. When you look through it, the world seems to shift slightly, as if you're seeing past surface illusions.`);
          },
          onUse() {
            if (disk.roomId === 'illusion_gallery') {
              println(`You hold the truth monocle to your eye and survey the Illusion Gallery. The confusing reflections and false doorways immediately become obvious—you can clearly see which paths are real and which are illusions. The way to the shimmering portal is now evident.`);
              
              disk.illusionPathFound = true;
              
              // Remove the block from the PORTAL exit (not UP)
              const room = getRoom('illusion_gallery');
              const portalExit = room.exits.find(exit => exit.dir === 'portal');
              if (portalExit && portalExit.block) {
                delete portalExit.block;
                println(`You can now clearly see the path to the portal.`);
              }
            } else if (disk.roomId === 'mirage_desert') {
              println(`Through the truth monocle, the mirages in the desert fade away, revealing the true path to the oasis. You can now proceed safely.`);
              disk.desertPathFound = true;
              
              // Remove block from northeast exit if it exists
              const room = getRoom('mirage_desert');
              const neExit = room.exits.find(exit => exit.dir === 'northeast');
              if (neExit && neExit.block) {
                delete neExit.block;
              }
            } else {
              println(`You look through the truth monocle. The world appears largely the same, but with subtle differences—colors are more vivid, edges more defined, and you sense you would be able to see through any deliberate deceptions.`);
            }
          }
        }        
      ],
      exits: [
        { dir: 'southeast', id: 'element_hall' },
      ]
    },
    {
      id: 'arcane_library',
      name: 'Archmage\'s Private Library',
      desc: `This hidden library is a treasure trove of arcane knowledge. Bookshelves stretch from floor to ceiling, filled with ancient tomes and scrolls. Magical lights drift among the shelves, illuminating titles as they pass.
      
      A massive **DESK** covered in open books and magical instruments dominates one side of the room. A comfortable **CHAIR** with worn cushions sits behind it.
      
      Magical **ARTIFACTS** of various kinds are displayed in glass cases along one wall. A peculiar **CLOCK** with multiple hands and unfamiliar symbols ticks softly.
      
      The hidden exit back to the Moonlight Chamber lies to the **SOUTH**.`,
      items: [
        {
          name: ['desk', 'study desk', 'archmage desk'],
          desc: `The enormous desk is crafted from dark wood with inlaid silver runes along its edges. Papers, open books, and half-finished magical calculations cover its surface. A crystal inkwell glows with luminescent blue ink.`,
          onUse() {
            println(`You examine the papers on the desk. Most contain incomprehensible magical formulas and diagrams, but one bears what appears to be a personal note:
            
            "Key to the Celestial Observatory should be kept with Thaddeus. The mechanism requires maintenance every month during the new moon. Must remember to properly align the astrolabe next time."
            
            There's also what appears to be a small **JOURNAL** partially hidden under some papers.`);

            const room = getRoom('arcane_library');
            if (room.journalRevealed) {
              return;
            }

            room.items.push({
              name: ['journal', 'archmage journal', 'zephyrian journal'],
              desc: `A small leather-bound journal with Z.E. embossed on the cover in gold. It appears to be the Archmage's personal diary.`,
              isTakeable: true,
              onTake() {
                println(`You take the Archmage's personal journal. It feels warm to the touch, as if recently handled.`);
              },
              onUse() {
                println(`You open the journal and flip through its pages. Most entries deal with magical experiments and tower maintenance, but a few personal reflections stand out:
                
                "The tower grows taller, but to what end? Each new floor, each new challenge... sometimes I wonder if I'm building a monument or a prison."
                
                "E's letters grow less frequent. Does she resent my obsession with the tower? Perhaps the Celestial Observatory will impress her; she always loved the stars."
                
                "Dream breakthrough today! If my calculations are correct, the Infinity Chamber at the tower's apex will allow transition between realities. Just need to solve the stability issue..."
                
                The final entry simply reads: "It's finished. Tonight, I ascend."`);
              }
            });

            room.journalRevealed = true;
          }
        },
        {
          name: ['chair', 'reading chair', 'comfortable chair'],
          desc: `A high-backed chair with worn velvet cushions. Despite its age, it looks remarkably comfortable—exactly what one would want for long hours of study.`,
          onUse() {
            println(`You sit in the Archmage's chair. It's incredibly comfortable, almost suspiciously so. You feel your mind clearing, and magical formulas on the nearby papers suddenly seem a bit more comprehensible, though still beyond your full understanding.`);
          }
        },
        {
          name: ['artifacts', 'magical artifacts', 'display cases'],
          desc: `Glass cases contain an assortment of magical items: a silver **WAND** with frost patterns along its length, a small **HOURGLASS** with multicolored sand that flows both up and down simultaneously, and a **COMPASS** whose needle spins in impossible patterns.`,
          onUse() {
            println(`The display cases appear to be magically sealed. However, one case has been left slightly open—the one containing the ice wand.`);

            const room = getRoom('arcane_library');
            if (room.wandRevealed) {
              return;
            }

            room.items.push({
              name: ['ice wand', 'silver wand', 'frost wand'],
              desc: `A slender silver wand etched with intricate frost patterns. It feels cold to the touch, and tiny ice crystals form in the air around its tip.`,
              isTakeable: true,
              onTake() {
                println(`You carefully remove the ice wand from its display case. A brief flurry of snowflakes swirls around your hand as you grasp it.`);
              },
              onUse() {
                if (disk.roomId === 'fire_chamber') {
                  println(`You point the ice wand at the bonfire. A beam of frost shoots forth, temporarily dampening the flames. Now might be a good moment to interact with the red crystal while the fire is subdued.`);
                } else {
                  println(`You wave the ice wand gently. The air around you cools momentarily, and a few snowflakes materialize before melting away.`);
                }
              }
            });

            room.wandRevealed = true;
          }
        },
        {
          name: ['clock', 'magical clock', 'strange clock'],
          desc: `This peculiar timepiece has multiple hands of different lengths and colors. Instead of numbers, the face displays arcane symbols that shift and change. Some hands move clockwise, others counterclockwise, and a few seem to jump randomly.`,
          onUse() {
            println(`You study the clock carefully, trying to decipher its meaning. After a few minutes, you realize it's not just telling time—it's tracking multiple timelines simultaneously. One set of hands seems to correspond to the current time in the tower, while others track different potential realities.`);
          }
        },
        {
          name: ['books', 'tomes', 'scrolls', 'bookshelves'],
          desc: `The library contains thousands of volumes on every conceivable magical subject. Many are written in languages you don't recognize, with letters that seem to move on the page. Titles include "Dimensional Folding: Theory and Practice," "The Seven Principles of Elemental Harmony," and "Beyond the Veil: Journeys Through Realities."`,
          onUse() {
            println(`You pull a book at random from the shelf—"Beginners' Guide to Tower Navigation"—and flip through it. Most of the content is too advanced, but one passage catches your eye:
            
            "Remember that the Spiral Tower exists in multiple dimensions simultaneously. Doors that appear locked may simply require a different perspective. What seems like a solid wall from one angle might be an open passage from another."
            
            Intrigued, you return the book to the shelf.`);
          }
        }
      ],
      exits: [
        { dir: 'south', id: 'moonlight_chamber' },
      ]
    },
    {
      id: 'whispering_corridor',
      name: 'Whispering Corridor',
      desc: `This long, narrow hallway curves gently, its far end lost in shadow. The walls are lined with portraits of solemn-faced wizards and witches, presumably former residents or notable visitors of the tower.
      
      As you walk, you hear soft whispers that seem to come from the walls themselves—fragments of conversations, magical incantations, and occasional laughter or crying.
      
      Softly glowing **ORBS** float near the ceiling, providing gentle illumination that follows you as you move. A **GARDENER** tends to small magical plants growing from wall sconces.
      
      The corridor connects to the foyer to the **WEST** and continues **EAST** deeper into this section of the tower.`,
      items: [
        {
          name: ['orbs', 'light orbs', 'floating lights'],
          desc: `Softball-sized spheres of pale blue light drift near the ceiling. They seem semi-intelligent, clustering around visitors to provide illumination wherever it's needed.`,
          onUse() {
            println(`You reach toward one of the light orbs. It playfully drifts just out of reach, then hovers closer when you withdraw your hand. It seems to be responding to your intentions rather than your actions.`);
          }
        },
        {
          name: ['portraits', 'paintings', 'picture frames'],
          desc: `Dozens of portraits line the walls, depicting stern-faced magical practitioners from various eras. Their eyes seem to follow you as you move past. Plaques beneath identify some famous names in magical history, along with years spanning centuries.`,
          onUse() {
            println(`As your hand nears one of the portraits, the painted figure stirs slightly and whispers: "Beware the Illusion Gallery. Remember that reality is what you believe it to be—nothing more, nothing less." The figure then returns to stillness.`);
          }
        }
      ],
      exits: [
        { dir: 'west', id: 'foyer' },
        { dir: 'east', id: 'garden_atrium' },
      ]
    },
    {
      id: 'garden_atrium',
      name: 'Magical Garden Atrium',
      desc: `The corridor opens into a stunning indoor garden filled with magical plants from across countless realms. Flowers that glow and change colors, trees bearing crystalline fruit, and vines that weave themselves into elegant patterns cover every surface.
      
      Glass walls and ceiling let in sunlight that seems to come from multiple suns of different colors, creating rainbow patterns across the garden floor. Small floating **ISLANDS** with additional plants hover at different heights throughout the atrium.
      
      A stone **FOUNTAIN** featuring a sculpture of a rising **PHOENIX** stands at the center. The water flowing from it sparkles with magical energy.
      
      Exits lead **WEST** back to the corridor, **NORTH** to another area, and a small door to the **EAST**.`,
      items: [
        {
          name: ['fountain', 'stone fountain', 'magical fountain'],
          desc: `The circular fountain is carved from iridescent stone that shifts colors with the light. Water flows from the outstretched wings of the phoenix sculpture, cascading down in patterns that occasionally form symbols or words before dissolving back into ordinary ripples.`,
          onUse() {
            println(`You dip your fingers in the fountain water. It feels energized, sending a pleasant tingling sensation up your arm. As you watch, the ripples from your touch form the word "ASCEND" before dissipating.`);
          }
        },
        {
          name: ['phoenix', 'phoenix statue', 'bird sculpture'],
          desc: `The stone phoenix is captured in a moment of rising, wings outstretched and head thrown back. It looks so lifelike you almost expect it to complete its motion and fly away. Occasionally, the stone glows faintly from within, as if housing an inner fire.`,
          onUse() {
            println(`As you touch the phoenix sculpture, it warms under your fingers. A small **FEATHER** made of gleaming red-gold stone or metal detaches from the wing and falls into your hand.`);

            if (!disk.inventory.some(item => item.name.includes('feather'))) {
              disk.inventory.push({
                name: ['phoenix feather', 'feather'],
                desc: `A feather that appears to be made of metal with the colors of fire—red fading to gold at the tip. Despite its metallic composition, it's incredibly light and occasionally emits tiny sparks.`,
                onUse() {
                  if (disk.roomId === 'air_chamber') {
                    println(`You hold out the phoenix feather toward the white crystal. The feather glows brightly, and suddenly the air currents in the room bend to your will! You direct a gust of wind toward the crystal, dislodging a small **AIR TOKEN** that was hidden within it. The token floats directly to your outstretched hand.`);

                    // Add the air token to inventory
                    if (!disk.inventory.some(item => item.name.includes('air token'))) {
                      disk.inventory.push({
                        name: ['air token', 'cloud token', 'white token'],
                        desc: `A small token made of a lightweight, opal-like material carved in the shape of a swirling cloud. It feels almost weightless.`,
                        onUse() {
                          if (disk.roomId === 'element_hall') {
                            println(`You place the air token in its corresponding indentation on the dais.`);
                          } else if (disk.roomId === 'chasm_bridge') {
                            println(`You hold up the air token. It glows softly, and the violent winds in the chasm calm momentarily, allowing you to cross safely.`);
                            goDir('north');
                          } else {
                            println(`You release the air token from your palm, and it hovers an inch above your hand before settling back down.`);
                          }
                        }
                      });
                    }
                  } else if (disk.roomId === 'chasm_bridge') {
                    println(`You hold up the phoenix feather. It bursts into flame but doesn't burn up. The feather creates a path of light across the chasm that you can walk on.`);
                    goDir('north');
                  } else {
                    println(`The feather grows warm in your hand and emits a soft, golden glow before cooling again.`);
                  }
                }
              });
            }
          }
        },
        {
          name: ['islands', 'floating islands', 'hovering gardens'],
          desc: `Small islands of earth and stone, ranging from the size of a dinner plate to large enough to hold a small tree, float at various heights. Each hosts different plant species, some familiar but many completely alien. They seem to rotate slowly around the room in a complex orbital pattern.`,
          onUse() {
            println(`You reach toward a lower-hanging island. It drifts slightly in your direction, allowing you to examine the unusual blue roses growing on it. Their petals are translucent, and they chime softly when you touch them.`);
          }
        },
        {
          name: ['plants', 'magical plants', 'unusual flora'],
          desc: `The variety of plant life is staggering. You see flowers that open and close in sequence as if breathing, trees with leaves that transform from green to crystal and back, vines that form themselves into decorative knots, and countless others. Many emit soft glows or gentle musical tones.`,
          onUse() {
            println(`You gently touch a few of the plants. Each responds differently—some retreat from your touch like shy animals, others lean toward you as if seeking attention. One small flower releases a puff of glittering pollen that makes you briefly see the garden from high above before the effect fades.`);
          }
        }
      ],
      exits: [
        { dir: 'west', id: 'whispering_corridor' },
        { dir: 'north', id: 'gardeners_workshop' },
        { dir: 'east', id: 'herbalist_nook' },
      ]
    },
    {
      id: 'gardeners_workshop',
      name: 'Gardener\'s Workshop',
      desc: `This cozy workspace is filled with gardening implements, plant specimens, and workbenches. Shelves along the walls hold countless pots, seeds, and labeled bottles of various magical plant foods and treatments.
      
      A large **WORKTABLE** in the center is cluttered with half-completed projects: partially grafted magical plants, sketches of new garden layouts, and notes on plant care. **TOOLS** hang neatly from hooks on one wall.
      
      Several small **TERRARIUMS** containing miniature ecosystems line another wall, each one a perfect tiny world with its own climate and inhabitants.
      
      The exit back to the garden atrium lies to the **SOUTH**.`,
      items: [
        {
          name: ['worktable', 'table', 'workbench'],
          desc: `The large wooden table is worn smooth from years of use. It's covered with ongoing projects: a cutting from a plant with leaves like stained glass, several sketches of garden layouts with notes in flowing script, and a partially completed topiary that occasionally reshapes itself as if seeking its final form.`,
          onUse() {
            println(`You examine the items on the worktable. Among the papers is a diagram labeled "Elemental Gardens - Maintenance Schedule" with notes about the care requirements for plants in four different gardens, each corresponding to an elemental chamber. There's also a small key with a tag reading "Toolshed."`)
          }
        },
        {
          name: ['tools', 'gardening tools', 'implements'],
          desc: `A comprehensive collection of gardening tools hangs from hooks on the wall. Many appear ordinary at first glance—trowels, pruning shears, watering cans—but closer inspection reveals magical modifications: a watering can that produces different types of water for different plants, self-sharpening shears, and a trowel that adjusts its size based on the job at hand.`,
          onUse() {
            if (disk.roomId === 'earth_chamber') {
              println(`You carefully dig at the base of the crystal tree with the garden spade. The soil is surprisingly loose, and you unearth a small **EARTH TOKEN** that was buried among the roots. You pick up the token and add it to your inventory.`);

              // Add the Earth Token to inventory if not already there
              if (!disk.inventory.some(item => item.name.includes('earth token'))) {
                disk.inventory.push({
                  name: ['earth token', 'leaf token', 'green token'],
                  desc: `A small token made of a deep emerald-like material carved in the shape of a leaf. It feels pleasantly heavy and cool in your palm.`,
                  onUse() {
                    if (disk.roomId === 'element_hall') {
                      println(`You place the earth token in its corresponding indentation on the dais.`);
                    } else {
                      println(`You roll the earth token between your fingers. It has a pleasant weight to it, reminiscent of rich soil.`);
                    }
                  }
                });
              }
            } else {
              println(`You make a few experimental digging motions with the trowel. It's well-crafted, but there's nothing to dig here.`);
            }
          }
        },
        {
          name: ['terrariums', 'miniature gardens', 'glass containers'],
          desc: `Glass containers of various sizes hold complete miniature ecosystems. One contains a tiny desert complete with cactus plants no bigger than your fingernail. Another houses a minuscule rainforest with working waterfalls and tiny birds flitting among trees the size of matches. A third shows a winter landscape with real snow falling inside.`,
          onUse() {
            println(`You tap gently on one of the terrariums—a tiny coastal scene with a working lighthouse and miniature waves lapping at a beach. The tiny lighthouse keeper waves up at you, and you hear the faint sound of a bell ringing across the miniature sea.`);
          }
        }
      ],
      exits: [
        { dir: 'south', id: 'garden_atrium' },
      ]
    },
    {
      id: 'herbalist_nook',
      name: 'Herbalist\'s Nook',
      desc: `This small, aromatic room is packed with dried herbs hanging from ceiling beams, bubbling potions in glass vials, and shelves filled with ingredients in labeled jars. The air is thick with mingled scents both pleasant and strange.
      
      A small **WORKTABLE** holds a mortar and pestle, scales, and other equipment for processing plants. A well-thumbed **BOOK** of recipes lies open beside an actively brewing **POTION**.
      
      The **HERBALIST** herself, an elderly woman with bright eyes and hair like silver wire framing her dark face, smiles warmly as you enter.
      
      The only exit leads **WEST** back to the garden atrium.`,
      items: [
        {
          name: ['worktable', 'preparation table', 'herbalist table'],
          desc: `A small wooden table stained with the residue of countless potions and herbal preparations. It holds various tools of the herbalist's trade: several mortars and pestles of different materials, delicate scales with gold weights, and an assortment of knives and scissors for plant preparation.`,
          onUse() {
            println(`You examine the items on the table. There are partially prepared ingredients—leaves mid-chop, seeds ready to be ground, and petals arranged by color. The herbalist watches you with amusement but doesn't interfere with your curiosity.`);
          }
        },
        {
          name: ['book', 'recipe book', 'herbal guide'],
          desc: `A large, leather-bound book with well-worn pages. It lies open to a recipe for "Clarity of Mind Elixir," with intricate illustrations of the preparation process and notes added in various inks, suggesting refinements made over many years.`,
          onUse() {
            println(`As you reach toward the book, the herbalist speaks up.
            
            "That's my personal recipe collection, with additions and modifications I've made over seventy years of practice. You're welcome to look, but I doubt you'd make much sense of my notation system."
            
            Indeed, the margins are filled with cryptic symbols and abbreviated notes that would take years to decipher.`);
          }
        },
        {
          name: ['potion', 'brewing potion', 'elixir'],
          desc: `A glass flask over a small flame contains a liquid that shifts between blue and purple. Occasional silver bubbles rise to the surface and pop with tiny musical notes. A sweet smell with undertones of mint and something exotic emanates from it.`,
          onUse() {
            println(`You reach toward the potion, and the herbalist chuckles.
            
            "Careful with that one," she says. "It's a perception enhancer I'm brewing for the Archmage's Illusion Gallery. One drop would have you seeing the true nature of things for hours—useful in that tricky place. But it's not quite ready yet; the musical bubbles need to change from C-minor to E-flat before it's stable."
            
            She gently moves the flask away from your reach.`);
          }
        }
      ],
      exits: [
        { dir: 'west', id: 'garden_atrium' },
      ]
    },
    
    {
      id: 'illusion_gallery',
      name: 'The Illusion Gallery',
      desc: `This disorienting chamber stretches in impossible directions. Mirrors line the walls, floor, and ceiling, creating infinite reflections that shift and change even when you stand still. Pathways appear and vanish as light plays through the room.
    
        **DOORWAYS** that lead nowhere alternate with **MIRRORS** that are actually passages. Your own reflection sometimes acts independently, moving differently or vanishing entirely.
    
        In the center floats a large crystal **PRISM** that casts rainbow light throughout the gallery, further complicating the illusions.
    
        Through the shifting illusions, you glimpse what appears to be a **PORTAL** leading to another realm entirely. The stairs also go back **DOWN** to the Elemental Hall.`,
      items: [
        {
          name: ['prism', 'crystal prism', 'floating crystal'],
          desc: `A large multifaceted crystal hovers at the center of the gallery, rotating slowly. It captures light from an unseen source and splits it into rainbow beams that dance across the mirrored surfaces, creating and dissolving illusions throughout the space.`,
          onUse() {
            println(`You reach out to touch the prism. Your hand passes through several illusory versions before finding the real crystal. As your fingers make contact, the room briefly stabilizes—illusions fade, and you can see the true layout of the gallery, including a shimmering portal that leads deeper into the tower.
            
            But the effect only lasts a moment before the illusions return in full force.`);
          }
        },
        {
          name: ['doorways', 'doors', 'passages'],
          desc: `Archways and doorframes appear throughout the gallery, but it's impossible to tell which are real and which are reflections. Some show other parts of the gallery, others impossible spaces that couldn't exist within the tower.`,
          onUse() {
            println(`You approach what looks like a doorway and cautiously extend your hand. It passes through empty air—this one is just an illusion. You'll need to test each potential passage carefully.`);
          }
        },
        {
          name: ['mirrors', 'reflective surfaces', 'looking glasses'],
          desc: `Mirrors of all shapes and sizes create a bewildering maze of reflections. Sometimes they show you as you are, sometimes as you might be in different circumstances, and sometimes they don't reflect you at all but show completely different scenes.`,
          onUse() {
            if (disk.inventory.some(item => item.name.includes('truth monocle'))) {
              println(`You hold up the truth monocle to examine the mirrors. Through it, you can clearly see which mirrors are actually passages—they appear slightly rippled, like the surface of water, rather than perfectly reflective.
              
              You identify the correct path through the reflective passages that leads to the mysterious portal.`);
              
              disk.illusionPathFound = true;
              
              // Remove the block from the portal exit
              const room = getRoom('illusion_gallery');
              const portalExit = room.exits.find(exit => exit.dir === 'portal');
              if (portalExit && portalExit.block) {
                delete portalExit.block;
                println(`You can now clearly see the path to the portal.`);
              }
            } else {
              println(`You touch one of the mirrors. Your hand meets cool, solid glass. But is it actually a mirror, or an illusion of one? And might some of these reflective surfaces actually be doorways? It's disorienting and confusing.`);
            }
          }
        },
        {
          name: ['portal', 'shimmering portal', 'dimensional gateway'],
          desc: `A circular area that ripples like the surface of a pond, barely visible through the maze of illusions. It seems to lead to another realm entirely.`,
          onUse() {
            if (disk.illusionPathFound) {
              println(`You step through the shimmering portal...`);
              goDir('portal');
            } else {
              println(`You can't find a clear path to the portal through all these illusions. You need a way to see through them first.`);
            }
          }
        }
      ],
      exits: [
        { dir: 'down', id: 'element_hall' },
        { 
          dir: 'portal', 
          id: 'dream_nexus', 
          block: `You try to reach the portal, but the illusions confuse your sense of direction. Every path you take leads back to where you started. The mirrors show contradictory directions, and you can't tell which passages are real. You need a way to see through these illusions first.` 
        },
      ],
      onEnter() {
        // Check if player has already solved the illusion
        if (disk.illusionPathFound) {
          const illusionGallery = getRoom('illusion_gallery');
          const portalExit = illusionGallery.exits.find(exit => exit.dir === 'portal');
          if (portalExit && portalExit.block) {
            delete portalExit.block;
            println(`The path through the illusions remains clear from your previous use of the truth monocle, leading to the shimmering portal.`);
          }
        }
      }
    },

    {
      id: 'observatory_approach',
      name: 'Observatory Approach',
      desc: `A grand spiral staircase ascends through this vertical chamber toward a domed ceiling far above. The walls are painted with cosmic scenes—swirling galaxies, shining stars, and celestial beings that subtly move when you're not looking directly at them.
      
      However, the staircase above appears to be blocked by an impenetrable magical barrier, and there seem to be no other exits from this chamber.
      
      **CONSTELLATIONS** painted on the ceiling shift to match the actual night sky outside the tower. A **MODEL** of the solar system floats in the center of the chamber, planets orbiting a glowing sun.`,
      items: [
        {
          name: ['constellations', 'star patterns', 'celestial map'],
          desc: `The constellations painted on the ceiling shift and change, perfectly matching the current positions of the stars outside. Some formations are familiar, while others depict mythological beings and magical creatures you've never seen in the actual night sky.`,
          onUse() {
            println(`You study the constellations closely. As you focus on one—a pattern resembling a spiral tower—the stars comprising it briefly shine brighter, and you hear a faint whisper: "The path to ascension requires completing all trials, not seeking shortcuts."`);
          }
        },
        {
          name: ['model', 'planetary model', 'solar system'],
          desc: `A perfect miniature of the solar system hovers in midair. The detail is extraordinary, but it seems to be merely decorative in this blocked chamber.`,
          onUse() {
            println(`You reach out to touch the model, but it phases through your fingers like an illusion. This chamber seems cut off from the tower's main progression.`);
          }
        }
      ],
      exits: [
        // No exits - this is now a dead end to prevent shortcuts
      ]
    },

    {
      id: 'dream_nexus',
      name: 'Dream Nexus',
      desc: `This impossible chamber seems to exist partially in the realm of dreams. The walls shift between solid stone and misty translucence. Floating islands of reality—fragments of other places and times—hover in a swirling void of colors and shapes that defy description.
      
      A **DREAMCATCHER** the size of a wagon wheel hangs in the center, slowly rotating. Dreams in the form of glowing wisps become entangled in its web, where they play out as miniature scenes before dissolving.
      
      The **DREAM WEAVER**, a tall figure with opalescent skin and eyes like distant stars, sits cross-legged on a floating cushion, weaving threads of dreams into patterns.
      
      A swirling **VOID** appears to lead to a desert realm, the only visible exit from this dream space.`,
      items: [
        {
          name: ['dreamcatcher', 'giant dreamcatcher', 'dream web'],
          desc: `The massive dreamcatcher is woven from silvery threads that seem partially solid and partially incorporeal. Dreams caught in its web manifest as tiny scenes played out within the strands—a flight over mountains, a conversation with long-dead relatives, fantastic creatures and impossible landscapes.`,
          onUse() {
            println(`You touch the edge of the dreamcatcher. A shiver runs through the web, and suddenly you're experiencing fragments of other people's dreams—flying over cities, speaking languages you don't know, feeling emotions not your own. The experience is disorienting but exhilarating.
            
            As you withdraw your hand, a small fragment of dream remains clinging to your fingers—a glowing wisp that shows a tiny scene of the tower's apex, with a doorway surrounded by shifting reality.`);
          }
        },
        {
          name: ['dream weaver', 'weaver', 'dream keeper'],
          desc: `A being unlike any you've seen before. Their form is humanoid but clearly not human—skin that shifts with opalescent colors, eyes like windows into deep space, and fingers impossibly long and delicate as they manipulate threads of dream-stuff. They wear robes that seem to be made from the fabric of night itself, embroidered with constellations.`,
          onUse() {
            println(`The Dream Weaver's eyes shift to you as you approach. You sense amusement rather than alarm.
            
            "Few find their way here by chance," they say in a voice that seems to come from within your own mind rather than from their mouth. "You seek the tower's peak, do you not? The realm where reality itself bends to will?
            
            "The path forward leads through trials of truth and perception. The desert of illusions awaits, where only those who can distinguish reality from mirage may proceed."
            
            The Weaver gestures toward the swirling void. "I cannot offer shortcuts, for the journey itself transforms the traveler."`);
    
            // Only give truth monocle if they don't have one
            if (!disk.inventory.some(item => item.name.includes('truth monocle'))) {
              println(`\nThe Weaver's long fingers pluck a thread from the air and weave it into a small circle, which solidifies into a crystal monocle.
              
              "This will aid you in seeing past deceptions, but remember—some truths are harder to bear than comfortable lies."`);
              
              disk.inventory.push({
                name: ['truth monocle', 'monocle'],
                desc: `A small monocle made from dream-crystal. When you look through it, illusions and falsehoods become transparent, revealing the truth beneath.`,
                onUse() {
                  if (disk.roomId === 'illusion_gallery') {
                    println(`You hold the truth monocle to your eye and survey the Illusion Gallery. The confusing reflections and false doorways immediately become obvious—you can clearly see which paths are real and which are illusions. The way to the portal is now evident.`);
                    disk.illusionPathFound = true;
                    
                    const room = getRoom('illusion_gallery');
                    const portalExit = room.exits.find(exit => exit.dir === 'portal');
                    if (portalExit && portalExit.block) {
                      delete portalExit.block;
                    }
                  } else if (disk.roomId === 'mirage_desert') {
                    println(`Through the truth monocle, the mirages in the desert fade away, revealing the true path to the oasis. You can now proceed safely.`);
                    disk.desertPathFound = true;
                    
                    const room = getRoom('mirage_desert');
                    const neExit = room.exits.find(exit => exit.dir === 'northeast');
                    if (neExit && neExit.block) {
                      delete neExit.block;
                    }
                  } else {
                    println(`You look through the truth monocle. The world appears largely the same, but with subtle differences—colors are more vivid, edges more defined, and you sense you would be able to see through any deliberate deceptions.`);
                  }
                }
              });
            }
          }
        }
      ],
      exits: [
        { dir: 'void', id: 'mirage_desert' },
      ]
    },


    {
      id: 'mirage_desert',
      name: 'Mirage Desert',
      desc: `You find yourself in what appears to be a vast desert within the confines of the tower—an impossibility that somehow exists. The sand stretches in all directions, with heat waves creating rippling **MIRAGES** on the horizon.
      
      Multiple suns of different colors hang in the artificial sky, casting overlapping shadows that point in contradictory directions. **OASES** appear and disappear in the distance as you watch.
      
      Strange crystalline **CACTI** grow from the sand, their surfaces reflecting the surroundings like mirrors, further confusing your sense of direction.
      
      A shimmering portal behind you leads back to the Dream Nexus.`,
      items: [
        {
          name: ['mirages', 'illusions', 'heat waves'],
          desc: `The mirages shimmer on the horizon, showing tantalizing visions of water, shade, and even structures that vanish when approached. They're indistinguishable from reality until you get close enough to pass through them.`,
          onUse() {
            println(`You walk toward one of the mirages—a beautiful oasis with palm trees. As you approach, it maintains its appearance until the moment you would reach it, then dissolves into empty air. The experience is disorienting.`);
          }
        },
        {
          name: ['oases', 'oasis', 'water pools'],
          desc: `Water pools surrounded by vegetation appear and disappear as you scan the horizon. They look perfectly real, complete with the sounds of water and wildlife, but which, if any, are actually real is impossible to determine from a distance.`,
          onUse() {
            if (disk.inventory.some(item => item.name.includes('truth monocle'))) {
              println(`You look through the truth monocle at the various oases. Most fade from view immediately, revealed as illusions. But one remains—a true oasis in the northeast direction, partially hidden behind a dune. You can now make your way there.`);
              disk.desertPathFound = true;
            } else {
              println(`You walk toward what appears to be the nearest oasis. As you get closer, it seems to move further away, always maintaining the same distance. This could be dangerous—people have been known to die of thirst chasing mirages in real deserts.`);
            }
          }
        },
        {
          name: ['cacti', 'crystal cacti', 'mirror plants'],
          desc: `Bizarre cacti with crystalline surfaces grow from the sand. Instead of the usual green flesh and spines, they have faceted surfaces that reflect their surroundings like funhouse mirrors, distorting the already confusing landscape further.`,
          onUse() {
            println(`You touch one of the crystal cacti carefully, avoiding its sharp edges. Rather than the expected coolness of glass or crystal, it feels strangely organic and slightly warm. Your reflection in its surface stares back at you, but seems to move with a slight delay, as if it were a separate entity mimicking you.`);
          }
        }
      ],
      exits: [
        { dir: 'portal', id: 'dream_nexus' },
        { dir: 'northeast', id: 'true_oasis', block: `You start walking northeast, but the shifting mirages and multiple contradictory shadows make it impossible to maintain your direction. You find yourself walking in circles, always returning to your starting point. There must be a way to see through these illusions.` },
      ],
      onEnter() {
        const mirageDesert = getRoom('mirage_desert');
        if (mirageDesert) {
          // Set the desertPathFound flag to true
          disk.desertPathFound = true;

          // Find the 'northeast' exit and remove its block
          const neExit = mirageDesert.exits.find(exit => exit.dir === 'northeast');
          if (neExit && neExit.block) {
            delete neExit.block;
            println(`Through the truth monocle, the mirages in the desert fade away, revealing the true path to the oasis. You can now clearly see the way NORTHEAST to the True Oasis.`);
          }
        }
      }
    },
    {
      id: 'true_oasis',
      name: 'The True Oasis',
      desc: `This small pocket of verdant life in the middle of the illusory desert is both peaceful and clearly magical. A spring of clear water bubbles up from between strange blue-green stones, filling a small pool before disappearing back into the sand.
      
      Trees unlike any you've seen on Earth provide shade with their broad, slightly luminescent leaves. **FRUITS** of various colors hang from their branches, giving off enticing aromas.
      
      A small stone **ALTAR** stands beside the pool, inscribed with symbols relating to truth and illusion. An elderly **HERMIT** in sun-bleached robes sits cross-legged nearby, smiling serenely at your approach.
      
      Beyond the oasis, a passage leads **NORTH** to another part of the tower.`,
      items: [
        {
          name: ['fruits', 'strange fruits', 'glowing fruits'],
          desc: `The fruits hanging from the oasis trees are unlike any you've seen before—some are perfect spheres that shift color as they ripen, others have complex geometric shapes with faceted skins, and still others appear to glow from within with soft blue or purple light.`,
          isTakeable: true,
          onTake() {
            println(`You pick one of the glowing fruits from a low-hanging branch. It feels strangely light in your hand, and emits a subtle, sweet fragrance.`);
          },
          onUse() {
            println(`You take a cautious bite of the strange fruit. The flavor is indescribable—somehow combining sweetness, tartness, and a hint of spice in a way that doesn't exist in ordinary food. As the juice trickles down your throat, your mind feels clearer and your senses sharper. The world around you seems slightly more vivid than before.`);
          }
        },
        {
          name: ['altar', 'stone altar', 'truth altar'],
          desc: `A small altar carved from a single block of blue-green stone, the same material that surrounds the spring. Symbols representing truth, revelation, and the distinction between reality and illusion are carved into its surfaces. A shallow depression in the top appears designed to hold water.`,
          onUse() {
            println(`You place your hands on the altar. The stone feels cool despite the desert heat, and the symbols carved into it seem to shift slightly under your fingers, rearranging themselves into new patterns. A sense of clarity washes over you, as if the altar is somehow focusing your thoughts.`);
          }
        },
        {
          name: ['spring', 'water spring', 'magical pool'],
          desc: `Crystal-clear water bubbles up from between the strange blue-green stones, filling a small pool before mysteriously being reabsorbed by the sand around its edges. The water glitters with tiny motes of light, as if it contains captured stars.`,
          onUse() {
            println(`You cup your hands and drink from the spring. The water is sweet and incredibly refreshing, immediately banishing any fatigue or thirst you felt. For a moment, your mind feels expanded—you can sense the entirety of the tower around you, from its foundations to its apex, as a single interconnected entity. The sensation fades quickly, but leaves you with a deeper understanding of the tower's nature.`);

            if (!disk.inventory.some(item => item.name.includes('fishing rod'))) {
              println(`As the expanded awareness fades, you notice a **FISHING ROD** leaning against a rock near the pool that you hadn't seen before.`);

              const room = getRoom('true_oasis');
              room.items.push({
                name: ['fishing rod', 'rod', 'fishing pole'],
                desc: `A slender fishing rod made from an unknown material that looks like bamboo but has the shimmering quality of mother-of-pearl. The line is so fine as to be nearly invisible, and the hook seems to be made from crystal.`,
                isTakeable: true,
                onTake() {
                  println(`You take the unusual fishing rod. Despite its delicate appearance, it feels sturdy in your hands.`);
                },
                onUse() {
                  if (disk.roomId === 'water_chamber') {
                    println(`You cast the line toward the hovering blue crystal. The nearly invisible line loops gracefully through the air. With some careful maneuvering, you might be able to retrieve something from near the crystal.`);
                  } else if (disk.roomId === 'true_oasis') {
                    println(`You playfully cast the line into the small pool. Surprisingly, the hook disappears beneath the surface as if the pool were much deeper than it appears. After a moment, you feel a tug and pull up... a small, silvery fish that wriggles in the air before dissolving into motes of light and disappearing. The hermit chuckles at your surprised expression.`);
                  } else {
                    println(`You mime a casting motion with the rod. The line extends much further than should be physically possible before retracting back to the rod when you will it.`);
                  }
                }
              });
            }
          }
        }
      ],
      exits: [
        { dir: 'south', id: 'mirage_desert' },
        { dir: 'north', id: 'chasm_bridge' },
      ]
    },

    {
      id: 'chasm_bridge',
      name: 'The Phantom Bridge',
      desc: `You stand at the edge of an impossibly wide chasm that cuts through this level of the tower. Far below, mists swirl in unfathomable depths. The opposite side is visible but seems impossibly distant given the tower's dimensions from outside.
    
        Violent **WINDS** howl through the chasm, strong enough to throw a person off balance. There's no physical bridge in sight, though there are stone platforms on both sides where one might have stood.
    
        A **PEDESTAL** with curious indentations stands on your side of the chasm. Ancient runes carved into it glow faintly with magical energy.
    
        Near the edge, four **CRYSTALS** of different colors are embedded in the stone, pulsing with inner light in a rhythmic pattern.
    
        The only exits are **SOUTH** back to the oasis, or **NORTH** across the chasm—if you can find a way to cross it.`,

        items: [
          {
            name: ['winds', 'howling winds', 'violent air'],
            desc: `Powerful gusts of wind rush through the chasm, carrying echoes of whispers that almost sound like words. The air here has an unnatural quality—the winds blow in seemingly contradictory directions simultaneously, creating dangerous turbulence.`,
            onUse() {
              println(`You cautiously extend your hand into the full force of the wind. It's incredibly powerful, nearly pushing you off balance even with just one arm exposed. Crossing without some form of protection or control over the winds would be suicide.`);
            }
          },
          {
            name: ['pedestal', 'stone pedestal', 'rune stand'],
            desc: `A waist-high pedestal of dark stone stands at the edge of the chasm. Its top surface contains several shallow indentations and a circular crystal lens in the center. Runes around the edge glow with a pale blue light.`,
            onUse() {
              println(`You examine the pedestal carefully. The runes appear to reference perception and reality. As you place your hands on its surface, the pedestal hums softly, and the crystal lens in its center begins to glow more brightly.
              
              Looking through the lens, you can now see patterns in the chaotic winds. They shift in a specific sequence that seems to correspond to the four elements: first the light winds of AIR swirl upward, then the heavy energies of EARTH press downward, followed by the fierce heat of FIRE blazing across, and finally the flowing currents of WATER completing the cycle.
              
              The four crystals embedded nearby seem to pulse in rhythm with this pattern.`);
        
              if (!disk.chasmPedestalActivated) {
                disk.chasmPedestalActivated = true;
                println(`\nThe pedestal is now activated. You can now interact with the individual colored crystals around the chasm's edge.`);
              }
            }
          },
          {
            name: ['crystals', 'four crystals'],
            desc: `Four crystals are embedded in the stone near the edge of the chasm—a clear BLUE CRYSTAL, a deep ORANGE CRYSTAL, a ruby GREEN CRYSTAL, and a sapphire WHITE CRYSTAL. They pulse with inner light and seem to be waiting for activation in some specific order.`,
            onUse() {
              if (!disk.chasmPedestalActivated) {
                println(`The crystals pulse with magical energy, but don't seem to respond to your touch. You need to activate the pedestal first to understand the pattern.`);
                return;
              }
        
              println(`The crystals are ready to be activated, but you need to touch each one individually in the correct sequence.              
              You see a BLUE CRYSTAL, an ORANGE CRYSTAL, a GREEN CRYSTAL, and a WHITE CRYSTAL.`);
            }
          },
          // Individual crystal interactions
          {
            name: ['white crystal', 'air crystal', 'clear crystal'],
            desc: `A clear white crystal that seems to contain swirling mists. It represents the element of air.`,
            onUse() {
              if (!disk.chasmPedestalActivated) {
                println(`The crystal doesn't respond. You need to activate the pedestal first.`);
                return;
              }
              
              if (disk.crystalSequenceSolved) {
                println(`The crystal is already activated as part of the completed sequence.`);
                return;
              }
        
              disk.crystalSequence = disk.crystalSequence || [];
              disk.crystalSequence.push('white');
              println(`You touch the white crystal. It flares with brilliant light and begins to glow steadily.`);
              checkCrystalSequence();
            }
          },
          {
            name: ['green crystal', 'earth crystal'],
            desc: `A deep green crystal that pulses with earthy energy. It represents the element of earth.`,
            onUse() {
              if (!disk.chasmPedestalActivated) {
                println(`The crystal doesn't respond. You need to activate the pedestal first.`);
                return;
              }
              
              if (disk.crystalSequenceSolved) {
                println(`The crystal is already activated as part of the completed sequence.`);
                return;
              }
        
              disk.crystalSequence = disk.crystalSequence || [];
              disk.crystalSequence.push('green');
              println(`You touch the green crystal. It flares with brilliant light and begins to glow steadily.`);
              checkCrystalSequence();
            }
          },
          {
            name: ['orange crystal', 'fire crystal', 'ruby crystal'],
            desc: `An orange crystal that pulses with inner fire.`,
            onUse() {
              if (!disk.chasmPedestalActivated) {
                println(`The crystal doesn't respond. You need to activate the pedestal first.`);
                return;
              }
              
              if (disk.crystalSequenceSolved) {
                println(`The crystal is already activated as part of the completed sequence.`);
                return;
              }
        
              disk.crystalSequence = disk.crystalSequence || [];
              disk.crystalSequence.push('orange');
              println(`You touch the orange crystal. It flares with brilliant light and begins to glow steadily.`);
              checkCrystalSequence();
            }
          },
          {
            name: ['blue crystal', 'water crystal', 'sapphire crystal'],
            desc: `A sapphire blue crystal that seems to contain flowing water. It represents the element of water.`,
            onUse() {
              if (!disk.chasmPedestalActivated) {
                println(`The crystal doesn't respond. You need to activate the pedestal first.`);
                return;
              }
              
              if (disk.crystalSequenceSolved) {
                println(`The crystal is already activated as part of the completed sequence.`);
                return;
              }
        
              disk.crystalSequence = disk.crystalSequence || [];
              disk.crystalSequence.push('blue');
              println(`You touch the blue crystal. It flares with brilliant light and begins to glow steadily.`);
              checkCrystalSequence();
            }
          }
        ],

      exits: [
        { dir: 'south', id: 'true_oasis' },
        { 
          dir: 'north', 
          id: 'time_vault', 
          block: `You look across the yawning chasm. The violent winds make crossing impossible without some way to stabilize them. You'll need to solve the puzzle of the colored crystals and pedestal first.` 
        },
      ],
      onEnter() {
        if (disk.crystalSequenceSolved) {
          const exit = getExit('north', getRoom('chasm_bridge').exits);
          if (exit && exit.block) {
            delete exit.block;
            println(`The bridge of solidified air remains in place from when you solved the crystal sequence, allowing safe passage across the chasm.`);
          }
        }
      }
    },
  
    {
      id: 'time_vault',
      name: 'The Time Vault',
      desc: `This circular chamber seems to exist in multiple moments simultaneously. Different sections of the room are illuminated as if at different times of day, and certain objects appear in multiple positions at once, showing past, present, and future states overlaid.
      
      A massive **HOURGLASS** stands in the center, taller than a person. The sand within it flows both upward and downward simultaneously, defying gravity. The area immediately around it seems to experience time differently—movements appear faster or slower when near it.
      
      **CLOCKS** of various designs line the walls, each showing a different time and each moving at a different rate. Some run backward, others skip erratically, and a few remain precisely synchronized.
      
      An elderly **CHRONOMANCER** in robes embroidered with clock faces and temporal symbols adjusts the mechanisms with careful precision.
      
      Exits lead **SOUTH** back to the Phantom Bridge and **UP** to continue ascending the tower.`,
      items: [
        {
          name: ['hourglass', 'giant hourglass', 'time artifact'],
          desc: `The massive hourglass is at least eight feet tall, mounted on a bronze frame inscribed with temporal runes. The glass itself seems to shimmer, as if not quite solid. Inside, golden sand flows both upward and downward simultaneously, creating patterns like galaxies in the center where the flows meet.`,
          onUse() {
            println(`You touch the frame of the hourglass. Immediately, your perception of time fractures—you see yourself from moments ago entering the room, while simultaneously experiencing a preview of yourself leaving it. The sensation is disorienting but not entirely unpleasant.
            
            The Chronomancer notices your reaction and nods knowingly.
            
            "The Temporal Confluence," he says. "It allows one to perceive the flow of time directly. Useful for understanding the tower's nature, wouldn't you say?"`);
          }
        },
        {
          name: ['clocks', 'timepieces', 'chronometers'],
          desc: `Dozens, perhaps hundreds, of clocks cover the walls in a massive display. They range from simple sundials to elaborate astronomical clocks and devices unlike anything you've seen before. Each shows a different time, and their ticking creates a complex rhythm that somehow feels harmonious rather than chaotic.`,
          onUse() {
            println(`You study the clocks, trying to make sense of their various times and speeds. One in particular catches your eye—a small pocket watch that seems to show the exact time in the normal world outside the tower. As you look at it, you realize how long you've been inside the tower, though it feels both longer and shorter than the actual elapsed time.`);
          }
        }
      ],
      exits: [
        { dir: 'south', id: 'chasm_bridge' },
        { dir: 'up', id: 'infinity_chamber' },
      ]
    },
    {
      id: 'observatory',
      name: 'Celestial Observatory',
      desc: `The observatory dome is a marvel of magical engineering. The ceiling is transparent, offering an unobstructed view of the sky, which appears strangely enhanced—stars shine brighter, planets appear larger, and celestial phenomena normally invisible to the naked eye are clearly visible.
    
      The centerpiece of the room is an enormous brass **TELESCOPE** mounted on a complex mechanism that allows it to point in any direction. Star charts and astronomical instruments cover the **TABLES** that line the circular walls.
      
      A control **PANEL** near the telescope is covered in dials, levers, and small viewing lenses. It appears to be locked.
      
      The stairs lead back **DOWN** to the approach chamber. Another staircase continues **UP** toward what must be the tower's apex.`,
      items: [
        {
          name: ['telescope', 'brass telescope', 'observatory telescope'],
          desc: `An enormous telescope of polished brass and crystal lenses dominates the center of the observatory. Its intricate gearing system allows it to be pointed at any part of the sky with incredible precision. The eyepiece appears to be enhanced with magical crystals that allow observation far beyond what normal optics could achieve.`,
          onUse() {
            const room = getRoom('observatory');
            if (!room.telescopeUnlocked) {
              println(`You try to adjust the telescope, but its controls are locked. A small keyhole on the control panel suggests you need a specific key to operate it.`);
              return;
            }

            println(`You peer through the telescope's eyepiece. The view is breathtaking—stars so clear and close they seem just beyond your reach, planets with visible surface details, and cosmic phenomena in vibrant colors. As you adjust the focus, you notice something unusual—a constellation that looks exactly like the Spiral Tower itself.
            
            As you center the telescope on this constellation, the stars comprising it brighten suddenly, and a beam of light travels down the telescope's length to the control panel, where it illuminates a previously invisible series of symbols.`);

            if (!room.pathRevealed) {
              println(`The symbols form a map of the tower's upper levels, revealing a hidden passage from the Time Vault to the Infinity Chamber. This could be invaluable information for reaching the tower's apex.`);
              room.pathRevealed = true;
            }
          }
        },
        {
          name: ['tables', 'star charts', 'astronomical tables'],
          desc: `Tables around the perimeter of the observatory are covered with star charts, astronomical calculations, and models of celestial bodies. Many of the charts show stars and planets that don't match any known astronomical bodies, perhaps from other realities or times.`,
          onUse() {
            println(`You examine the star charts carefully. Most are incomprehensible to you, featuring astronomical bodies you've never seen and calculations that seem to incorporate magical as well as mathematical principles.
            
            One chart, however, catches your eye—it shows the tower itself, but from an impossible perspective, as if viewed from above and from all sides simultaneously. Notes in the margin refer to something called "The Infinity Chamber" at the tower's peak.`);
          }
        },
        {
          name: ['panel', 'control panel', 'telescope controls'],
          desc: `A complex panel of brass and crystal covered in dials, levers, and small viewing lenses. Some controls are labeled with astronomical symbols, others with magical runes. A small keyhole suggests the controls are locked.`,
          onUse() {
            const room = getRoom('observatory');
            if (!room.telescopeUnlocked) {
              println(`The control panel appears to be locked. There's a small keyhole that probably requires a specific key.`);
              return;
            }

            println(`With the telescope unlocked, you can now adjust the controls. Dials allow you to set coordinates, levers adjust focus and magnification, and small viewing lenses show different aspects of whatever celestial body the telescope is pointed at.
            
            As you experiment with the controls, you realize the telescope is capable of observing not just distant objects, but different times and even parallel realities. This is far beyond any normal astronomical instrument.`);
          }
        }
      ],
      exits: [
        { dir: 'up', id: 'time_vault' },
        { dir: 'down', id: 'observatory_approach' },
      ]
    },
    {
      id: 'infinity_chamber',
      name: 'The Infinity Chamber',
      desc: `You've reached the pinnacle of the Spiral Tower—the legendary Infinity Chamber. The circular room exists in a space that seems both impossibly vast and intimately small simultaneously. The walls, ceiling, and floor are indistinguishable, made of a substance that shifts between transparency and mirror-like reflection.
      
      In the center floats a **NEXUS** of pure magical energy—a swirling vortex of light and color that connects to countless other realities. Glimpses of other worlds and possibilities flash within its depths.
      
      The **ARCHMAGE ZEPHYRIAN** himself stands before the Nexus, a tall figure with a star-white beard and robes that seem to contain moving constellations. He turns as you enter, a knowing smile on his ancient face.
      
      The only exit is back **DOWN** to the Time Vault, though the Nexus itself seems to lead... elsewhere.`,
      items: [
        {
          name: ['nexus', 'energy nexus', 'magical vortex'],
          desc: `The Nexus is a swirling vortex of pure magical energy, connecting the tower to countless other dimensions and realities. Within its shifting patterns, you catch glimpses of other worlds, alternate histories, and possible futures. It pulses with power that feels both incredibly ancient and newly born.`,
          onUse() {
            println(`You cautiously extend your hand toward the Nexus. The energy responds to your presence, tendrils of light reaching out to touch your fingertips. You feel a connection to all possible versions of yourself across infinite realities. The sensation is overwhelming but not unpleasant.
            
            Archmage Zephyrian nods approvingly.
            
            "Few have the courage to touch the Nexus directly," he says. "You've proven yourself worthy indeed."`);
          }
        },
        {
          name: ['Archmage Zephyrian', 'Zephyrian', 'archmage'],
          desc: `Zephyrian is tall and imposing, with a long white beard and eyes that contain swirling galaxies. His robes shift and move as if containing actual stars and constellations within their fabric. Despite his clearly immense power and age, he has a kind face and an air of quiet wisdom.`,
          onUse() {
            println(`As you approach Zephyrian, he smiles warmly.
            
            "Welcome, Seeker," he says. "You've overcome the challenges of my tower and proven yourself worthy of reaching the apex. Few manage such a feat. You may speak with me freely now—ask your questions, or make your wish if you prefer. The power of the Infinity Chamber is at your disposal."
            
            You sense that you can TALK to Zephyrian to learn more.`);
          }
        }
      ],
      exits: [
        { dir: 'down', id: 'time_vault' },
      ],
      onEnter() {
        // Add Zephyrian as a character
        if (!getCharacter('Zephyrian')) {
          disk.characters.push({
            name: ['Archmage Zephyrian', 'Zephyrian', 'archmage'],
            roomId: 'infinity_chamber',
            desc: `Zephyrian is tall and imposing, with a long white beard and eyes that contain swirling galaxies. His robes shift and move as if containing actual stars and constellations within their fabric. Despite his clearly immense power and age, he has a kind face and an air of quiet wisdom.`,
            onTalk: () => println(`Archmage Zephyrian turns his cosmic gaze to you, a gentle smile appearing through his star-white beard.
            
            "You've come a long way, Seeker," he says. "What would you like to know?"`),
            topics: [
              {
                option: `Ask about the **TOWER**.`,
                line: `"The Spiral Tower is my greatest creation," Zephyrian says, his voice resonating with pride and perhaps a hint of melancholy. "I built it as both a testament to what magic can achieve and as a test for those who would seek true understanding.
                
                "The tower exists simultaneously in multiple realities, which is why it can contain spaces that seem impossible. Each challenge, each puzzle, was designed not merely to obstruct, but to teach. Those who reach this chamber have learned not just about magic, but about themselves."
                
                He gestures to the walls, which briefly become transparent, showing the tower's full spiral structure from an impossible external viewpoint. "It has been my home, my laboratory, and my legacy for over a millennium."`
              },
              {
                option: `Ask about a **REWARD**.`,
                line: `"Ah, the least interesting of the options, but I supposed you have earned it. Send this code to u/root88 on Reddit to receive your reward: "master-of-the-tower-and-pride-of-zephyrian"`,
                onSelected: ({disk, println, getRoom, enterRoom}) => {
                  // Get the current room
                  const room = getRoom('infinity_chamber');
                  
                  // Clear all existing exits
                  room.exits = [];
                  
                  // Add only the URL exit
                  room.exits.push({ 
                    dir: 'portal', 
                    id: 'https://www.reddit.com/u/root88', 
                    isURL: true
                  });
                  
                  // Remove Zephyrian from characters array
                  disk.characters = disk.characters.filter(char => 
                    !(char.name.includes('Zephyrian') || char.name.includes('archmage'))
                  );
                  
                  // Also remove him from the room directly
                  room.desc = room.desc.replace(/The \*\*ARCHMAGE ZEPHYRIAN\*\* himself stands before the Nexus.+?ancient face\./g, 
                    "The room is now empty except for the swirling portal.");
                  
                  // Make Zephyrian item inaccessible 
                  room.items = room.items.filter(item => 
                    !(item.name.includes('Zephyrian') || item.name.includes('archmage'))
                  );
                  
                  // Prevent talk command from working
                  disk.conversant = undefined;
                  disk.conversation = undefined;
                  
                  println(`\nA swirling **PORTAL** has appeared. It is your only way forward. Zephyrian has vanished.`);
                }                
              },
              {
                option: `Ask about the **NEXUS**.`,
                line: `Zephyrian turns to regard the swirling energies at the center of the chamber.
                
                "The Infinity Nexus is a confluence of all possible realities," he explains. "It exists at a point where dimensions touch, allowing passage between them. I didn't create it so much as discover it and build the tower around it.
                
                "Through the Nexus, one can travel to any reality, any possibility. One could even create new realities, with sufficient understanding and power." He glances at you. "It is incredibly dangerous for the unprepared mind. Many who sought it were driven mad by the infinite possibilities they glimpsed.
                
                "But you... you have proven resilient. You could use it, if you wished. To return home with new knowledge, to visit other worlds, or even to make a single wish come true."`
              },
              {
                option: `Make a **WISH**.`,
                line: `Zephyrian nods solemnly. "The tower's promise is fulfilled, then. You wish to use the power of the Nexus to make your deepest desire reality."
                
                He gestures toward the swirling vortex of energy. "Step forward and touch the Nexus with both hands. Focus your mind on your truest wish—not merely a fleeting desire, but what you truly want in the depths of your soul. The Nexus will respond accordingly.
                
                "But be warned," he adds, his voice growing serious. "The Nexus grants the wish that lives in your heart, not necessarily the one in your mind. It sees through self-deception. And once granted, a wish cannot be undone."
                
                He steps aside, leaving the path to the Nexus clear. "The choice is yours. Wish wisely, or perhaps choose not to wish at all. That too is wisdom."`
              },
              {
                option: `Ask about **ELARA**.`,
                line: `The Archmage's expression changes subtly—surprise, followed by a profound sadness that seems to age him further.
                
                "You found her letters," he says quietly. "Yes, Elara was... important to me. Another mage of great power, my equal in many ways, my superior in others." A smile briefly touches his lips. "She helped design many of the tower's chambers, particularly the Moonlight Chamber. Her specialty was illusion and lunar magic.
                
                "We had... differences of opinion about the tower's purpose. She saw it as a potential school, a place to teach new generations. I became increasingly focused on the Nexus and the realities beyond.
                
                "Eventually, she left." The stars in his robes dim slightly. "She founded her own academy in the mortal realm. We corresponded for decades after, but eventually... well, time passes differently for me now. I sometimes lose track of mortal lifespans."
                
                He turns away briefly, composing himself. "I believe her academy still stands, though it has been centuries since her passing. The Golden Crescent Academy, in the far northern lands."`
              },
              {
                option: `Say you would like to **LEAVE** without making a wish.`,
                line: `Zephyrian regards you with newfound respect. "A rare choice," he says. "Most who reach this chamber immediately seize the opportunity to wish. To walk away shows wisdom I seldom see." He waves his hand, and a doorway appears in the wall—a simple wooden door that seems oddly mundane in this extraordinary place. "This will take you back to where you entered the tower, with all the knowledge and experience you've gained intact. You may return someday, if you wish—the tower will remember you. "Or perhaps our paths will cross again in other realities. I journey often through the Nexus these days, exploring what lies beyond." He bows slightly. "Farewell, Seeker. You have impressed me today."`,
                onSelected: ({disk, println, getRoom, enterRoom}) => {
                  // Get the current room
                  const room = getRoom('infinity_chamber');
                  
                  // Clear all existing exits
                  room.exits = [];
                  
                  // Add only the URL exit
                  room.exits.push({ 
                    dir: 'portal', 
                    id: '/', 
                    isURL: true
                  });
                  
                  // Remove Zephyrian from characters array
                  disk.characters = disk.characters.filter(char => 
                    !(char.name.includes('Zephyrian') || char.name.includes('archmage'))
                  );
                  
                  // Also remove him from the room directly
                  room.desc = room.desc.replace(/The \*\*ARCHMAGE ZEPHYRIAN\*\* himself stands before the Nexus.+?ancient face\./g, 
                    "The room is now empty except for the swirling portal.");
                  
                  // Make Zephyrian item inaccessible 
                  room.items = room.items.filter(item => 
                    !(item.name.includes('Zephyrian') || item.name.includes('archmage'))
                  );
                  
                  // Prevent talk command from working
                  disk.conversant = undefined;
                  disk.conversation = undefined;
                  
                  println(`\nA swirling **PORTAL** has appeared. It is your only way forward. Zephyrian has vanished.`);
                }                
              },
              {
                option: `Ask to **EXPLORE** other realities through the Nexus.`,
                line: `Zephyrian's eyes light up with genuine pleasure.
                
                "A kindred spirit!" he exclaims. "Not content with a simple wish, but curious about what lies beyond... Yes, I can guide you through the Nexus to other realities.                
                "It would be a journey of unknown duration and destination. We might visit worlds where magic flows like water, or places where the laws of nature are utterly different from your home. We could explore alternate histories, possible futures, or realms entirely separate from the timeline you know."                              
                His expression grows serious. "But know that such journeys change a person. You may never see your home reality the same way again, if you see it at all. This is not a choice to make lightly."`
              },
              {
                option: `Ask to **STUDY** with Zephyrian.`,
                line: `The Archmage considers you thoughtfully, stroking his star-white beard.                
                "It has been centuries since I took an apprentice. My last one eventually built her own tower, though not nearly as interesting as this one." He smiles at the memory.                
                "You have shown aptitude, determination, and wisdom in your journey here. These are the foundations upon which great magic can be built." He paces slowly, considering.                
                "Very well. If that is your wish, I will teach you. Not merely spells and incantations, but the true nature of reality and how it may be shaped. Your training would take decades, perhaps centuries—but time works differently here at the tower's apex."                
                He stops pacing and faces you directly. "Be certain this is what you want. The path of magic is rewarding but demanding. Start by reading these books."`,
                onSelected: ({disk, println, getRoom, enterRoom}) => {
                  // Get the current room
                  const room = getRoom('infinity_chamber');
                  
                  // Clear all existing exits
                  room.exits = [];
                  
                  // Add only the URL exit
                  room.exits.push({ 
                    dir: 'portal', 
                    id: '/floor/250/library-portal-floor/', 
                    isURL: true
                  });
                  
                  // Remove Zephyrian from characters array
                  disk.characters = disk.characters.filter(char => 
                    !(char.name.includes('Zephyrian') || char.name.includes('archmage'))
                  );
                  
                  // Also remove him from the room directly
                  room.desc = room.desc.replace(/The \*\*ARCHMAGE ZEPHYRIAN\*\* himself stands before the Nexus.+?ancient face\./g, 
                    "The room is now empty except for the swirling portal.");
                  
                  // Make Zephyrian item inaccessible 
                  room.items = room.items.filter(item => 
                    !(item.name.includes('Zephyrian') || item.name.includes('archmage'))
                  );
                  
                  // Prevent talk command from working
                  disk.conversant = undefined;
                  disk.conversation = undefined;
                  
                  println(`\nA swirling **PORTAL** has appeared. It is your only way forward. Zephyrian has vanished.`);
                },
                removeOnRead: true,                
              },
              {
                option: `Accuse him of **ABANDONING** his responsibilities to the tower.`,
                line: `The warmth in Zephyrian's expression vanishes instantly. The stars in his robes pulse with angry red light, and the air around you grows heavy with power. "Abandonment?" His voice is dangerously quiet. "I've dedicated a millennium to the advancement of magical knowledge. I built this tower as both sanctuary and proving ground." With a dismissive gesture, a doorway materializes in the chamber wall. "This audience is over" An invisible force propels you toward a portal that opened in front of you. It seems questioning an archmage's choices was unwise.`,
                onSelected: ({disk, println, getRoom, enterRoom}) => {
                  // Get the current room
                  const room = getRoom('infinity_chamber');
                  
                  // Clear all existing exits
                  room.exits = [];
                  
                  // Add only the URL exit
                  room.exits.push({ 
                    dir: 'portal', 
                    id: '/the-void', 
                    isURL: true
                  });
                  
                  // Remove Zephyrian from characters array
                  disk.characters = disk.characters.filter(char => 
                    !(char.name.includes('Zephyrian') || char.name.includes('archmage'))
                  );
                  
                  // Also remove him from the room directly
                  room.desc = room.desc.replace(/The \*\*ARCHMAGE ZEPHYRIAN\*\* himself stands before the Nexus.+?ancient face\./g, 
                    "The room is now empty except for the swirling portal.");
                  
                  // Make Zephyrian item inaccessible 
                  room.items = room.items.filter(item => 
                    !(item.name.includes('Zephyrian') || item.name.includes('archmage'))
                  );
                  
                  // Prevent talk command from working
                  disk.conversant = undefined;
                  disk.conversation = undefined;
                  
                  println(`\nA swirling **PORTAL** has appeared. It is your only way forward. Zephyrian has vanished.`);
                },
                removeOnRead: true,
              }
            ]
          });
        }
      }
    },
  ],
  characters: [
    // Character 1: The Keeper
    {
      name: ['Keeper', 'Thaddeus', 'tower keeper'],
      roomId: 'foyer',
      desc: `An elderly man with a long silver beard and deep purple robes embroidered with arcane symbols. Despite his apparent age, he stands straight and his eyes are sharp and alert. He emanates an aura of calm authority.`,
      img: `
      /\\__/\\
     /      \\
    |  o  o  |
    |   <>   |
    |  \\__/  |
     \\______/
       |  |
  `,
      onTalk: () => println(`The Keeper bows slightly. "Welcome to the Spiral Tower, seeker. I am Thaddeus, keeper of this ancient place. How may I assist you?"`),
      topics: [
        {
          option: `Ask about the **TOWER**.`,
          line: `"The Spiral Tower has stood for a millennium, created by the great Archmage Zephyrian as both a residence and a test. Those who reach the top are said to be granted a single wish." He gestures upward. "The path is challenging, designed to test the mind, body, and spirit. Many have tried; few have succeeded."`,
          removeOnRead: true,
        },
        {
          option: `Ask about **ZEPHYRIAN**.`,
          line: `"Archmage Zephyrian is one of the greatest mages to ever live. He built this tower as both his home and as a way to find worthy successors to his knowledge." The Keeper's eyes grow distant. "It has been decades since he was last seen. Some say he travels between worlds now, exploring realities beyond our comprehension."`,
          removeOnRead: true,
        },
        {
          option: `Ask about the **WISH**.`,
          line: `"It is said that those who reach the tower's apex and meet with Zephyrian himself are granted a single wish - anything their heart truly desires." He smiles slightly. "Of course, wishes are complicated things. I've heard tales of seekers whose wishes were granted in unexpected ways. The tower teaches that clarity of purpose is as important as determination."`,
          removeOnRead: true,
        },
        {
          option: `Ask about the **GATE** to the stairs.`,
          line: `The Keeper glances at the locked gate blocking access to the upward staircase. "Ah, that. It's a security measure. Only those who have proven their understanding of the tower's elemental nature may proceed beyond the basic floors." He strokes his beard thoughtfully. "If you can unlock and enter the Moonlight Chamber, you'll find a way past that gate."`,
          prereqs: ['tower'],
        }
      ]
    },

    // Character 2: The Herbalist
    {
      name: ['Herbalist', 'Elowen', 'plant woman'],
      roomId: 'herbalist_nook',
      desc: `An elderly woman with skin the color of dark oak and hair like silver wire woven with small flowers and leaves. Her hands are stained with plant pigments, and she moves with deliberate grace. Her robes are earthy green with patterns of healing herbs embroidered along the hems.`,
      img: `
       ,,,
      (o o)
     (  ^  )
    /|\\___/|\\
      |   |
  `,
      onTalk: () => println(`The herbalist looks up from her work and smiles warmly. "Welcome to my little corner of the tower. I am Elowen. Do you seek knowledge of plants and their properties, or perhaps something specific?"`),
      topics: [
        {
          option: `Ask about her **POTIONS**.`,
          line: `"I create remedies for the tower's residents and occasionally for worthy visitors," she says, gesturing to the bubbling concoctions. "Healing elixirs, clarity draughts, potions to enhance magical abilities temporarily." She taps a blue vial. "This one helps one see through illusions—useful in certain parts of the tower, though I'm not permitted to simply give it away. The tower insists visitors solve its challenges properly."`,
          removeOnRead: true,
        },
        {
          option: `Ask about the **PLANTS**.`,
          line: `"The tower contains flora from countless realms," Elowen explains, her eyes lighting up with passion. "Some respond to touch, others sing when the moonlight hits them. The Archmage created special environments throughout the tower to sustain them." She gently touches a leaf that folds at her touch. "This one can predict storms three days before they arrive, and this," she points to a purple flower, "can temporarily allow one to understand animal speech when brewed correctly."`,
          removeOnRead: true,
        },
        {
          option: `Ask about **ZEPHYRIAN**.`,
          line: `"The Archmage?" She smiles fondly. "He had a surprising passion for herbalism despite his mastery of the grand magics. He would spend hours cataloging new plant species from other realms." Her expression grows wistful. "He brought me here nearly fifty years ago after discovering my modest herb shop. I haven't aged a day since—one of the tower's peculiarities." She chuckles. "Zephyrian himself became increasingly... detached from our reality in his later years. His journeys took him further and further from the physical world."`,
          removeOnRead: true,
        },
        {
          option: `Ask if she has any **ADVICE**.`,
          line: `Elowen considers for a moment. "The tower responds to genuine curiosity and respect. Don't try to force your way through its challenges—that never works. Instead, observe carefully and listen to what the tower is trying to teach you." She smiles and adds, "And remember that elements are not just physical forces but philosophical principles. Understanding that distinction will help you, especially in the elemental chambers."`,
          removeOnRead: true,
        }
      ]
    },

    // Character 3: The Gardener
    {
      name: ['Gardener', 'Finn', 'botanical keeper'],
      roomId: 'whispering_corridor',
      desc: `A slender young man with earth-toned clothing and fingers stained with soil. Vines with tiny blue flowers grow through his curly brown hair as if they've taken root there. He hums softly to himself as he tends to the magical plants growing from the wall sconces.`,
      img: `
      _|_
     / o \\
    |     |
     \\___/
      | |
  `,
      onTalk: () => println(`The gardener looks up with a bright smile. "Oh! Hello there! I'm Finn, caretaker of the tower's living greenery. Not many visitors stop to chat with me. What can I help you with?"`),
      topics: [
        {
          option: `Ask about the **PLANTS**.`,
          line: `"The plants here are quite extraordinary," Finn says excitedly. "Some are from distant lands, others from different planes of existence entirely. Each requires specific care—some need moonlight instead of sunlight, others drink pure magic instead of water." He gently strokes a vine that curls affectionately around his wrist. "They're more aware than most people realize. They listen to everything said in these halls."`,
          removeOnRead: true,
        },
        {
          option: `Ask about his **JOB**.`,
          line: `"I maintain the magical flora throughout the tower," he explains, pruning a small bush with crystal flowers. "It's more than just watering and pruning—many of these plants have complex magical needs and temperaments." He smiles fondly at a nearby flowering vine. "I was an ordinary gardener before the tower... called to me, I suppose you could say. I dreamed of it for weeks before finding myself at its entrance one morning. That was seven years ago. The plants and I have an understanding now."`,
          removeOnRead: true,
        },
        {
          option: `Ask if he has any **TOOLS** to spare.`,
          line: `"Tools?" Finn pats his pockets. "I keep most of my specialized equipment in my workshop, but..." He rummages through a small pouch at his belt. "Here, you can borrow this small trowel if you need it. Perfect for digging in magical soil, and enchanted to never break. Just bring it back when you're done, yes? The crystal birch saplings need replanting tomorrow."`,
          onSelected() {
            if (!disk.inventory.some(item => item.name.includes('garden spade'))) {
              disk.inventory.push({
                name: ['garden spade', 'spade', 'small trowel'],
                desc: `A small but sturdy garden trowel with intricate vines etched into its handle. Despite its delicate appearance, it feels remarkably strong.`,
                onUse() {
                  if (disk.roomId === 'earth_chamber') {
                    println(`You carefully dig at the base of the crystal tree with the garden spade. The soil is surprisingly loose, and you unearth a small **EARTH TOKEN** that was buried among the roots. You pick up the token and add it to your inventory.`);

                    // Add the Earth Token to inventory if not already there
                    if (!disk.inventory.some(item => item.name.includes('earth token'))) {
                      disk.inventory.push({
                        name: ['earth token', 'leaf token', 'green token'],
                        desc: `A small token made of a deep emerald-like material carved in the shape of a leaf. It feels pleasantly heavy and cool in your palm.`,
                        onUse() {
                          if (disk.roomId === 'element_hall') {
                            println(`You place the earth token in its corresponding indentation on the dais.`);
                          } else {
                            println(`You roll the earth token between your fingers. It has a pleasant weight to it, reminiscent of rich soil.`);
                          }
                        }
                      });
                    }
                  } else {
                    println(`You make a few experimental digging motions with the trowel. It's well-crafted, but there's nothing to dig here.`);
                  }
                }
              });
              println(`Finn hands you a small but sturdy garden trowel. "It's enchanted to sense magical soil components, so the blade will glow faintly when near magically enriched earth. Might come in handy."`);
            } else {
              println(`"Oh, I see you already have a gardening tool. Best not to mix different magical implements—they can get temperamental when they sense competition."`);
            }
          },
          removeOnRead: true,
        }
      ]
    },

    // Character 4: The Hermit
    {
      name: ['Hermit', 'Solus', 'desert sage'],
      roomId: 'true_oasis',
      desc: `An elderly figure in sun-bleached robes, with skin wrinkled and darkened from decades under desert suns. Their gender is indeterminate, and their eyes are a startling crystalline blue. They sit cross-legged by the oasis pool, seemingly untroubled by the heat or passage of time.`,
      img: `
     _____
    /     \\
   |  -+-  |
    \\_____/
      | |
  `,
      onTalk: () => println(`The hermit opens their eyes slowly, as if awakening from a deep meditation. "Ah, a traveler in the lands of illusion. Welcome to the True Oasis. What guidance do you seek?"`),
      topics: [
        {
          option: `Ask about the **DESERT**.`,
          line: `"This desert exists in a space between realities," the hermit explains, their voice calm and melodious. "It was created as both a test and a metaphor—to reach truth, one must see past appearances." They gesture at the mirages visible on the horizon. "The unwary chase illusions until they exhaust themselves. Only those who can distinguish reality from deception find this place."`,
          removeOnRead: true,
        },
        {
          option: `Ask about the **OASIS**.`,
          line: `"The True Oasis is exactly what its name suggests—the only genuine wellspring in a realm of falsehoods." The hermit scoops water from the pool, letting it run through their fingers. "The water here doesn't just quench physical thirst, but the thirst for clarity and understanding. Those who drink from it find their perception enhanced, if only temporarily."`,
          removeOnRead: true,
        },
        {
          option: `Ask how to cross the **CHASM**.`,
          line: `The hermit's eyes twinkle with knowing amusement. "Ah, the Phantom Bridge. Another of Zephyrian's tests of perception. The bridge exists and doesn't exist simultaneously." They trace a symbol in the air that briefly shimmers. "Two elements may help you cross—the element of air to calm the chaotic winds, and the element of fire to illuminate the unseen path. The phoenix's gift is particularly powerful there."`,
          removeOnRead: true,
        },
        {
          option: `Ask about the **FISHING ROD**.`,
          line: `The hermit glances at the unusual fishing rod. "An interesting tool, isn't it? I crafted it from materials gathered across multiple realities—the rod from bamboo that grows in dreamscapes, the line from the silk of invisible spiders, the hook from crystallized time." They smile gently. "It can catch things that exist beyond normal reach—useful for retrieving objects separated by water or space."`,
          prereqs: ['oasis'],
          removeOnRead: true,
        }
      ]
    },

    // Character 5: The Chronomancer
    {
      name: ['Chronomancer', 'Tempus', 'time mage'],
      roomId: 'time_vault',
      desc: `A tall, thin man with skin that occasionally flickers between youthful smoothness and extreme age. His robes are embroidered with clock faces that actually function, each showing a different time. His eyes appear to contain spinning clock gears instead of pupils, and his movements are precise and deliberate, as if choreographed in advance.`,
      img: `
     /^\\
    /   \\
   | o o |
   |  ∞  |
    \\___/
     | |
  `,
      onTalk: () => println(`The Chronomancer's attention shifts to you, though he appears to have been aware of your arrival before you entered. "Right on schedule," he says with a slight smile. "You have questions, I presume? About time, perhaps? Or the tower's temporal peculiarities?"`),
      topics: [
        {
          option: `Ask about **TIME** in the tower.`,
          line: `"Time flows... differently here," the Chronomancer explains, his age shifting subtly as he speaks. "In some chambers, it moves more quickly; in others, more slowly. This vault exists in multiple moments simultaneously." He gestures to the giant hourglass. "The tower itself exists partially outside conventional temporal flow—which is why those who dwell here age slowly, if at all. Spend a century here, and you might return to find only days have passed in the outside world. Or vice versa."`,
          removeOnRead: true,
        },
        {
          option: `Ask about the **HOURGLASS**.`,
          line: `"The Temporal Confluence," he says reverently, approaching the massive hourglass. "It's a physical manifestation of time's non-linear nature. The sand represents moments, flowing in all directions simultaneously." He passes his hand through the glass without resistance. "Through it, one can observe past and future events, though altering them is... problematic. Zephyrian used it to predict possible futures and guard against catastrophic outcomes. I maintain it in his absence."`,
          removeOnRead: true,
        },
        {
          option: `Ask about **ZEPHYRIAN**.`,
          line: `The Chronomancer's expression grows complex. "The Archmage exists in multiple timestreams simultaneously now. From his perspective, he is both here in the tower and exploring countless other realities." He adjusts one of the clocks on his robe. "I last saw him—or will see him, it's somewhat confused—in what you would consider three years from now, though to him it might be millennia." He sighs. "Time becomes rather personal once you step outside its normal flow."`,
          removeOnRead: true,
        },
        {
          option: `Ask about the path to the **APEX**.`,
          line: `"The final ascent?" The Chronomancer studies you carefully. "Time, space, and dimension blur near the tower's apex. The conventional path may not be the most direct route." He points to a clock on the wall showing an impossible time. "The observatory offers a perspective that transcends normal sight. From there, one might see connections between places that aren't apparent from here." He pauses. "You understand, of course, that reaching the apex changes a seeker irrevocably. Time will never flow quite the same for you again."`,
          removeOnRead: true,
        }
      ]
    },

    // Character 6: The Dream Weaver
    {
      name: ['Dream Weaver', 'Oneiros', 'dream keeper'],
      roomId: 'dream_nexus',
      desc: `A being of indeterminate gender with opalescent skin that shifts colors with their emotions. Their eyes contain swirling galaxies instead of irises, and their fingers are impossibly long and delicate. They wear robes that seem to be made from the fabric of night itself, with constellations and nebulae moving slowly across the material.`,
      img: `
     *o*
    /   \\
   |  ~  |
    \\___/
     |||
  `,
      onTalk: () => println(`The Dream Weaver's attention shifts to you, their galaxy-eyes focusing with gentle curiosity. "A conscious mind, walking the dream paths with purpose," they observe, their voice resonating directly in your thoughts rather than through the air. "What do you seek among the realms of possibility?"`),
      topics: [
        {
          option: `Ask about the **DREAM NEXUS**.`,
          line: `"This chamber exists at the intersection of consciousness and reality," the Dream Weaver explains, their voice rippling with harmonics. "Dreams are not merely illusions but glimpses of alternate possibilities—realities that might have been or might yet be." They gesture at the floating islands of dream-stuff. "Here, the barriers between imagination and existence are... permeable. The Archmage created this nexus to explore potential futures without committing to them."`,
          removeOnRead: true,
        },
        {
          option: `Ask about the **DREAMCATCHER**.`,
          line: `"A tool of my craft," they say with a gesture that sends ripples through the massive web. "It captures dreams from sleepers throughout the tower—and beyond. Each strand contains experiences, memories, and possibilities." The Weaver plucks a strand, which vibrates like a harp string. "I study these patterns to identify significant convergences—moments where many possible futures align. Such moments are rare and precious. They represent nexus points where reality's course might be altered."`,
          removeOnRead: true,
        },
        {
          option: `Ask about the **TRUTH MONOCLE**.`,
          line: `The Weaver's fingers weave intricate patterns in the air as they speak. "It is crafted from solidified dream-essence—the rare moments when countless dreamers perceive the same truth simultaneously." They indicate the crystal lens. "When viewed through it, illusions cannot maintain their cohesion. The mind perceives what truly is, rather than what appears to be." Their galaxy-eyes fix on yours. "Use it wisely. Some illusions exist as merciful shields against harsher realities."`,
          prereqs: ['dream nexus'],
          removeOnRead: true,
        },
        {
          option: `Ask about **ZEPHYRIAN**.`,
          line: `"The Archmage walks the dream paths often," the Weaver says, their skin shifting to deep blue hues. "His consciousness has expanded beyond single-reality constraints. Sometimes he appears here, observing the dream-flows, though increasingly his attention fixates on... deeper realms." They gesture at the void beyond the nexus. "He seeks what lies beyond the furthest dreams, at the edge of all possible realities. I fear sometimes that if he finds it, he may never return to forms we would recognize."`,
          removeOnRead: true,
        }
      ]
    },
    // Character 7: The Blue Robot
    {
      name: ['blue robot', 'azure automaton', 'mechanical assistant'],
      roomId: 'lab',
      desc: `A sleek, humanoid robot with a seamless azure-blue metal shell. Its eyes glow with gentle white light, and occasional sparks of magical energy dance across its surface. Unlike most mechanical constructs, it moves with an almost organic grace.`,
      img: `
    /-\\
   |o o|
   | - |
   /---\\
    | |
    | |
  `,
      onTalk: () => println(`The blue robot turns toward you, its eyes brightening slightly. "Hello, visitor. I am Model AZ-5, research assistant and caretaker of this laboratory. How may I assist you with your inquiries?"`),
      topics: [
        {
          option: `Ask about the **LAB**.`,
          line: `"This is Research Laboratory Alpha, where Archmage Zephyrian conducted experiments on trans-dimensional energy manipulation," the robot explains in a melodious voice. "The technologies and magical principles developed here were instrumental in creating several of the tower's unique chambers, particularly the Dream Nexus and the Infinity Chamber." It gestures to various apparatus around the room. "Much of the equipment remains functional, though I maintain it in standby mode for safety."`,
          removeOnRead: true,
        },
        {
          option: `Ask about its **CREATION**.`,
          line: `"I am a magical-mechanical hybrid construct," the robot says, a note of pride in its synthetic voice. "Archmage Zephyrian created me to assist with experiments too dangerous for organic assistants. My shell channels and regulates magical energy, while my cognitive matrix combines alchemical processes with enchanted crystal memory storage." It taps its chest, which emits a musical tone. "I have served in this laboratory for 157 years, 3 months, and 42 days. The Archmage's craftsmanship has proven remarkably durable."`,
          removeOnRead: true,
        },
        {
          option: `Ask about **ZEPHYRIAN**.`,
          line: `The robot's eyes dim slightly. "The Archmage last visited this laboratory 29 years, 7 months ago. He was working on theories regarding the fundamental nature of reality—specifically, whether all possible realities exist simultaneously and might be accessed with the correct approach." It makes a gesture that somehow conveys wistfulness despite its mechanical nature. "He left rather abruptly, mid-experiment, mentioning a breakthrough that required immediate testing at the tower's apex. He instructed me to maintain the laboratory until his return, a duty I continue to fulfill."`,
          removeOnRead: true,
        },
        {
          option: `Ask if it can provide any **TOOLS**.`,
          line: `"Most equipment here is calibrated for specific experimental procedures," the robot explains, "but I am authorized to provide certain basic tools to qualified visitors." It opens a compartment in its side and withdraws a small device. "This is an Elemental Resonance Detector. It vibrates when in proximity to concentrated elemental energy—most useful in the tower's elemental chambers." The robot extends the device toward you. "Would you like to borrow it? It must be returned before you leave the tower."`,
          onSelected() {
            if (!disk.inventory.some(item => item.name.includes('elemental detector'))) {
              disk.inventory.push({
                name: ['elemental detector', 'resonance detector', 'magical device'],
                desc: `A small handheld device made of polished brass with four different colored crystals embedded in its surface. It vibrates and the crystals glow when near sources of elemental energy.`,
                onUse() {
                  if (disk.roomId.includes('chamber')) {
                    println(`You activate the detector. The ${disk.roomId.includes('fire') ? 'red' : disk.roomId.includes('water') ? 'blue' : disk.roomId.includes('air') ? 'white' : 'green'} crystal glows brightly and the device vibrates, indicating a strong elemental presence nearby.`);
                  } else {
                    println(`You activate the detector, but none of the crystals glow strongly. There doesn't seem to be any concentrated elemental energy nearby.`);
                  }
                }
              });
              println(`The blue robot hands you the small brass device. "The orange crystal responds to fire, blue to water, white to air, and green to earth. When all four glow simultaneously, it indicates a point where the elements converge—potentially significant locations in the tower's magical ecosystem."`);
            } else {
              println(`"I see you already possess an elemental detection device," the robot observes. "Multiple detectors can create interference patterns that yield false readings. Your existing device should be sufficient for standard explorations."`);
            }
          },
          removeOnRead: true,
        }
      ]
    },

    // Character 8: The Red Robot
    {
      name: ['red robot', 'crimson automaton', 'advanced construct'],
      roomId: 'advanced',
      desc: `A humanoid robot with a gleaming crimson shell that seems to absorb rather than reflect light. Unlike the blue robot, this one's construction is more angular and imposing. Its eyes glow with amber light, and complex magical circuits occasionally pulse visible beneath seams in its metallic skin.`,
      img: `
    /-\\
   |^ ^|
   | v |
   /---\\
    | |
    | |
  `,
      onTalk: () => println(`The red robot turns with precise, calculated movements. "Visitor identified. Access to Advanced Research Laboratory: granted. I am Prototype RX-9, advanced arcano-mechanical intelligence. Inquiry parameters: open. How may I facilitate your research objectives?"`),
      topics: [
        {
          option: `Ask about the **ADVANCED LAB**.`,
          line: `"This facility contains Archmage Zephyrian's experimental research into consciousness transference, reality manipulation, and existence beyond conventional dimensional constraints," the robot states, its voice deeper and more mechanically inflected than its blue counterpart. "Access restriction level: minimal. Hazard potential: significant. The black void environment is a controlled non-space designed to eliminate external variables during consciousness experiments."`,
          removeOnRead: true,
        },
        {
          option: `Ask about its **CAPABILITIES**.`,
          line: `"This unit possesses advanced cognition matrices, reality anchoring systems, and partial autonomous creative subroutines," the robot explains with a hint of pride. "I function as both experimental subject and laboratory assistant. My shell contains seventeen exotic materials, including solidified shadow and crystallized time." It demonstrates by briefly phasing its arm through a nearby table. "Limited non-corporeality is among my features. I have participated in 1,522 experimental procedures and retain complete data on all of them."`,
          removeOnRead: true,
        },
        {
          option: `Ask about **ZEPHYRIAN'S** research.`,
          line: `The robot's eyes pulse in a pattern suggesting calculation. "The Archmage's final research focus: transcending conventional existence parameters. Working theory: consciousness is not bound to physical or temporal constraints, but can exist independently across multiple reality frameworks simultaneously." It gestures to complex diagrams floating in the void. "Final experiment designated 'Project Infinity' was conducted 27 years ago. The Archmage successfully transferred his conscious matrix to a non-corporeal state with multi-dimensional awareness. Current status: technically functional but with unforeseen consequences regarding reconnection to singular reality frameworks."`,
          removeOnRead: true,
        },
        {
          option: `Ask if it can **HELP** you reach the tower's apex.`,
          line: `"Direct intervention in seeker trials: prohibited by core directives," the robot states, though its tone seems regretful. "However, general information provision: permitted." Its chest panel opens, revealing a small compartment. "This navigation matrix may prove useful. It contains partial schematics of the tower's upper levels." A small crystal disc floats out from the compartment toward you. "Tower reconfiguration occurs periodically. Data accuracy: approximately 87.3%. Use with appropriate caution."`,
          onSelected() {
            if (!disk.inventory.some(item => item.name.includes('navigation crystal'))) {
              disk.inventory.push({
                name: ['navigation crystal', 'tower schematic', 'map crystal'],
                desc: `A small disc of crystals that projects a three-dimensional schematic of the tower's upper levels when held up to light. Certain sections appear incomplete or shift between multiple possible configurations.`,
                onUse() {
                  if (disk.roomId === 'observatory_approach' || disk.roomId === 'observatory') {
                    println(`You hold the crystal up to the light. It projects a detailed schematic of the observatory and surrounding chambers. You notice a hidden connection between the Time Vault and the Infinity Chamber that isn't apparent from normal observation—a shortcut that might bypass some of the tower's final challenges.`);
                    const timeVault = getRoom('time_vault');
                    if (!timeVault.exits.find(exit => exit.dir === 'secret passage')) {
                      timeVault.exits.push({ dir: 'secret passage', id: 'infinity_chamber' });
                    }
                  } else {
                    println(`You hold the crystal up to the light. It projects a complex three-dimensional schematic of the tower's upper levels. While fascinating, without reference to your current location, it's difficult to extract immediately useful navigational data.`);
                  }
                }
              });
              println(`The red robot provides you with a small crystal disc. "Activation method: expose to direct light source. Operational duration: unlimited. Tower schematic will highlight optimal pathways based on current position analysis when possible. Warning: certain tower sections employ non-Euclidean geometries not fully representable in three-dimensional projection."`);
            } else {
              println(`"You already possess a navigation assistance device," the robot observes. "Multiple interface systems may create contradictory guidance. Recommendation: utilize existing device to avoid navigational confusion."`);
            }
          },
          removeOnRead: true,
        }
      ]
    },

    // Character 9: The Phoenix Statue
    {
      name: ['phoenix statue', 'stone phoenix', 'animated statue'],
      roomId: 'garden_atrium',
      desc: `What initially appeared to be a stone statue of a phoenix has revealed itself to be something more—a magical construct with limited animation and awareness. Its stone feathers occasionally ripple with inner fire, and its eyes glow with amber light when it speaks.`,
      img: `
     ^v^
    /   \\
   <     >
    \\___/
     | |
  `,
      onTalk: () => println(`The stone phoenix's eyes ignite with inner flame. When it speaks, its beak doesn't move, but a voice like crackling fire resonates directly in your mind. "Few notice I am more than stone. What wisdom do you seek from one who burns yet is not consumed?"`),
      topics: [
        {
          option: `Ask about the **PHOENIX**.`,
          line: `"I am both less and more than a true phoenix," the statue's voice crackles in your mind. "The Archmage captured a fragment of a dying phoenix's essence during its rebirth cycle and bound it to this stone form. I retain memories and certain powers, but cannot fully manifest my fiery nature." The stone feathers briefly glow with inner heat. "I serve as guardian of these gardens and keeper of transformation secrets. The phoenix's domain is rebirth and renewal—appropriate for a tower dedicated to transcendence."`,
          removeOnRead: true,
        },
        {
          option: `Ask about the **FEATHER**.`,
          line: `"The feather you received is no mere stone trinket," the phoenix explains, its mental voice warming. "It contains a spark of true phoenix fire—eternal flame that illuminates rather than destroys. In this tower's context, it represents the triumph of illumination over illusion, and the bridge between thought and manifestation." The statue's eyes glow brighter. "Use it where darkness obscures your path, or where the immaterial must be made tangible. It is particularly powerful in the realm of air, with which fire shares ancient kinship."`,
          prereqs: ['phoenix'],
          removeOnRead: true,
        },
        {
          option: `Ask about **ZEPHYRIAN**.`,
          line: `The phoenix's stone form seems to warm slightly. "The Archmage and I share a certain understanding. Both of us have transcended our original forms, though through different means." Its mental voice grows distant. "He visited often in the early centuries, seeking insight into the phoenix's cycle of death and rebirth. His final questions concerned whether consciousness could survive transformation between states of existence without losing its essential nature. I believe his current absence is itself an experiment in such transformation."`,
          removeOnRead: true,
        },
        {
          option: `Ask about the **TOWER'S** purpose.`,
          line: `"The tower is Zephyrian's greatest experiment," the phoenix's thoughts resonate. "On one level, it seeks worthy successors to his knowledge. On another, it is a laboratory for studying reality's fundamental nature." The statue's eyes flare briefly. "But at its core, I believe it represents his attempt to understand transcendence itself—the phoenix principle applied to consciousness. Can a mind, like a phoenix, die to its current form and be reborn as something greater? The tower's challenges are designed to transform those who overcome them, not merely test their existing abilities."`,
          removeOnRead: true,
        }
      ]
    },
    // Character 10: Archmage Zephyrian -THESE ARE THE ONES ACTUALLY USED
    {
      name: ['Archmage Zephyrian', 'Zephyrian', 'archmage'],
      roomId: 'infinity_chamber',
      desc: `A tall, imposing figure with a flowing star-white beard and eyes that contain swirling galaxies. His robes shift and move as if containing actual stars and constellations within their fabric. Despite his clearly immense power and age, he has a kind face and an air of quiet wisdom.`,
      img: `
     /^\\
    /   \\
   |* ~ *|
   |  #  |
    \\___/
    / | \\
  `,
      onTalk: () => println(`Archmage Zephyrian turns his cosmic gaze to you, a gentle smile appearing through his star-white beard. "You've come a long way, Seeker," he says.`),
      topics: [
        {
          option: `Ask about the **TOWER**.`,
          line: `"The Spiral Tower is my greatest creation," Zephyrian says, his voice resonating with pride and perhaps a hint of melancholy. "I built it as both a testament to what magic can achieve and as a test for those who would seek true understanding. "The tower exists simultaneously in multiple realities, which is why it can contain spaces that seem impossible. Each challenge, each puzzle, was designed not merely to obstruct, but to teach. Those who reach this chamber have learned not just about magic, but about themselves."
          He gestures to the walls, which briefly become transparent, showing the tower's full spiral structure from an impossible external viewpoint. "It has been my home, my laboratory, and my legacy for over a millennium."`
        },
        {
          option: `Ask about a **REWARD**.`,
          line: `"Ah, the least interesting of the options, but I supposed you have earned it. Send this code to u/root88 on Reddit to receive your reward: "master-of-the-tower-and-pride-of-zephyrian"`,
          onSelected: ({disk, println, getRoom, enterRoom}) => {
            // Get the current room
            const room = getRoom('infinity_chamber');
            
            // Clear all existing exits
            room.exits = [];
            
            // Add only the URL exit
            room.exits.push({ 
              dir: 'portal', 
              id: 'https://www.reddit.com/u/root88', 
              isURL: true
            });
            
            // Remove Zephyrian from characters array
            disk.characters = disk.characters.filter(char => 
              !(char.name.includes('Zephyrian') || char.name.includes('archmage'))
            );
            
            // Also remove him from the room directly
            room.desc = room.desc.replace(/The \*\*ARCHMAGE ZEPHYRIAN\*\* himself stands before the Nexus.+?ancient face\./g, 
              "The room is now empty except for the swirling portal.");
            
            // Make Zephyrian item inaccessible 
            room.items = room.items.filter(item => 
              !(item.name.includes('Zephyrian') || item.name.includes('archmage'))
            );
            
            // Prevent talk command from working
            disk.conversant = undefined;
            disk.conversation = undefined;
            
            println(`\nA swirling **PORTAL** has appeared. It is your only way forward. Zephyrian has vanished.`);
          }          
        },
        {
          option: `Ask about the **NEXUS**.`,
          line: `Zephyrian turns to regard the swirling energies at the center of the chamber. "The Infinity Nexus is a confluence of all possible realities," he explains. "It exists at a point where dimensions touch, allowing passage between them. I didn't create it so much as discover it and build the tower around it. "Through the Nexus, one can travel to any reality, any possibility. One could even create new realities, with sufficient understanding and power." He glances at you. "It is incredibly dangerous for the unprepared mind. Many who sought it were driven mad by the infinite possibilities they glimpsed. "But you... you have proven resilient. You could use it, if you wished. To return home with new knowledge, to visit other worlds, or even to make a single wish come true."`
        },
        {
          option: `Make a **WISH**.`,
          line: `Zephyrian nods solemnly. "The tower's promise is fulfilled, then. You wish to use the power of the Nexus to make your deepest desire reality." He gestures toward the swirling vortex of energy. "Step forward and touch the Nexus with both hands. Focus your mind on your truest wish—not merely a fleeting desire, but what you truly want in the depths of your soul. The Nexus will respond accordingly. "But be warned," he adds, his voice growing serious. "The Nexus grants the wish that lives in your heart, not necessarily the one in your mind. It sees through self-deception. And once granted, a wish cannot be undone." He steps aside, leaving the path to the Nexus clear. "The choice is yours. Wish wisely, or perhaps choose not to wish at all. That too is wisdom."`
        },
        {
          option: `Ask about **ELARA**.`,
          line: `The Archmage's expression changes subtly—surprise, followed by a profound sadness that seems to age him further. "You found her letters," he says quietly. "Yes, Elara was... important to me. Another mage of great power, my equal in many ways, my superior in others. She helped design many of the tower's chambers, particularly the Moonlight Chamber. We had... differences of opinion about the tower's purpose. She saw it as a potential school, a place to teach new generations. I became increasingly focused on the Nexus and the realities beyond. Eventually, she left." The stars in his robes dim slightly. "She founded her own academy in the mortal realm. We corresponded for decades after, but eventually... well, time passes differently for me now. I sometimes lose track of mortal lifespans."`
        },
        {
          option: `Say you would like to **LEAVE** without making a wish.`,
          line: `Zephyrian regards you with newfound respect. "A rare choice," he says. "Most who reach this chamber immediately seize the opportunity to wish. To walk away shows wisdom I seldom see." He waves his hand, and a doorway appears in the wall—a simple wooden door that seems oddly mundane in this extraordinary place. "This will take you back to where you entered the tower, with all the knowledge and experience you've gained intact. You may return someday, if you wish—the tower will remember you. "Or perhaps our paths will cross again in other realities. I journey often through the Nexus these days, exploring what lies beyond." He bows slightly. "Farewell, Seeker. You have impressed me today."`,
          onSelected: ({disk, println, getRoom, enterRoom}) => {
            // Get the current room
            const room = getRoom('infinity_chamber');
            
            // Clear all existing exits
            room.exits = [];
            
            // Add only the URL exit
            room.exits.push({ 
              dir: 'portal', 
              id: '/', 
              isURL: true
            });
            
            // Remove Zephyrian from characters array
            disk.characters = disk.characters.filter(char => 
              !(char.name.includes('Zephyrian') || char.name.includes('archmage'))
            );
            
            // Also remove him from the room directly
            room.desc = room.desc.replace(/The \*\*ARCHMAGE ZEPHYRIAN\*\* himself stands before the Nexus.+?ancient face\./g, 
              "The room is now empty except for the swirling portal.");
            
            // Make Zephyrian item inaccessible 
            room.items = room.items.filter(item => 
              !(item.name.includes('Zephyrian') || item.name.includes('archmage'))
            );
            
            // Prevent talk command from working
            disk.conversant = undefined;
            disk.conversation = undefined;
            
            println(`\nA swirling **PORTAL** has appeared. It is your only way forward. Zephyrian has vanished.`);
          }          
        },
        {
          option: `Ask to **EXPLORE** other realities through the Nexus.`,
          line: `Zephyrian's eyes light up with genuine pleasure. "A kindred spirit!" he exclaims. "Not content with a simple wish, but curious about what lies beyond... Yes, I can guide you through the Nexus to other realities. "It would be a journey of unknown duration and destination. We might visit worlds where magic flows like water, or places where the laws of nature are utterly different from your home. We could explore alternate histories, possible futures, or realms entirely separate from the timeline you know. If you truly wish this, come. I have visited a thousand realities and barely scratched the surface of what exists. Know that such journeys change a person. You may never see your home reality the same way again, if you see it at all. This is not a choice to make lightly."`
        },
        {
          option: `Ask to **STUDY** with Zephyrian.`,
          line: `The Archmage considers you thoughtfully, stroking his star-white beard. "A student..." he muses. "It has been centuries since I took an apprentice. My last one eventually built her own tower, though not nearly as interesting as this one." He smiles at the memory. "You have shown aptitude, determination, and wisdom in your journey here. These are the foundations upon which great magic can be built." He paces slowly, considering. "Very well. If that is your wish, I will teach you. Your training would take decades, perhaps centuries—but time works differently here at the tower's apex." He stops pacing and faces you directly. "Be certain this is what you want. The path of magic is rewarding but demanding. It will transform you in ways you cannot predict. Start by reading these books."`,
          onSelected: ({disk, println, getRoom, enterRoom}) => {
            // Get the current room
            const room = getRoom('infinity_chamber');
            
            // Clear all existing exits
            room.exits = [];
            
            // Add only the URL exit
            room.exits.push({ 
              dir: 'portal', 
              id: '/floor/250/library-portal-floor/', 
              isURL: true
            });
            
            // Remove Zephyrian from characters array
            disk.characters = disk.characters.filter(char => 
              !(char.name.includes('Zephyrian') || char.name.includes('archmage'))
            );
            
            // Also remove him from the room directly
            room.desc = room.desc.replace(/The \*\*ARCHMAGE ZEPHYRIAN\*\* himself stands before the Nexus.+?ancient face\./g, 
              "The room is now empty except for the swirling portal.");
            
            // Make Zephyrian item inaccessible 
            room.items = room.items.filter(item => 
              !(item.name.includes('Zephyrian') || item.name.includes('archmage'))
            );
            
            // Prevent talk command from working
            disk.conversant = undefined;
            disk.conversation = undefined;
            
            println(`\nA swirling **PORTAL** has appeared. It is your only way forward. Zephyrian has vanished.`);
          },
          removeOnRead: true,           
        },
        {
          option: `Accuse him of **ABANDONING** his responsibilities to the tower.`,
          line: `The warmth in Zephyrian's expression vanishes instantly. The stars in his robes pulse with angry red light, and the air around you grows heavy with power. "Abandonment?" His voice is dangerously quiet. "I've dedicated a millennium to the advancement of magical knowledge. I built this tower as both sanctuary and proving ground." With a dismissive gesture, a doorway materializes in the chamber wall. "This audience is over" An invisible force propels you toward a portal that opened in front of you. It seems questioning an archmage's choices was unwise.`,
          onSelected: ({disk, println, getRoom, enterRoom}) => {
            // Get the current room
            const room = getRoom('infinity_chamber');
            
            // Clear all existing exits
            room.exits = [];
            
            // Add only the URL exit
            room.exits.push({ 
              dir: 'portal', 
              id: '/the-void', 
              isURL: true
            });
            
            // Remove Zephyrian from characters array
            disk.characters = disk.characters.filter(char => 
              !(char.name.includes('Zephyrian') || char.name.includes('archmage'))
            );
            
            // Also remove him from the room directly
            room.desc = room.desc.replace(/The \*\*ARCHMAGE ZEPHYRIAN\*\* himself stands before the Nexus.+?ancient face\./g, 
              "The room is now empty except for the swirling portal.");
            
            // Make Zephyrian item inaccessible 
            room.items = room.items.filter(item => 
              !(item.name.includes('Zephyrian') || item.name.includes('archmage'))
            );
            
            // Prevent talk command from working
            disk.conversant = undefined;
            disk.conversation = undefined;
            
            println(`\nA swirling **PORTAL** has appeared. It is your only way forward. Zephyrian has vanished.`);
          },
          removeOnRead: true,
        }
      ]
    },

  ],
  inventory: [],

  // Custom initialization for the game
  onLoad: () => {
    println(`Welcome to The Spiral Tower, a text adventure within a magical spiral tower filled with puzzles, characters, and mysteries. Type HELP to see available commands, or LOOK to begin exploring.`);
  },

  unlock: () => {
    println("You focus your mind and magical energy. The tower responds to your will, and you sense that some passages have been cleared.");
    // Unblock specific exits or gates as needed
    const reception = getRoom('reception');
    if (reception) {
      const upExit = getExit('up', reception.exits);
      if (upExit && upExit.block) {
        delete upExit.block;
        println("The gate blocking the stairs has opened!");
      }
    }
  }
});

// Load the disk
loadDisk(spiralTowerDisk);
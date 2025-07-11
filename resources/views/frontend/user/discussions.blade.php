<script>
document.addEventListener('DOMContentLoaded', function() {
  // If chat_id and subuser_id are present in the URL, open the correct chat window automatically
  const urlParams = new URLSearchParams(window.location.search);
  const notifChatId = urlParams.get('chat_id');
  const notifSubuserId = urlParams.get('subuser_id');
  fetch('/direct-chat/discussions')
    .then(res => res.json())
    .then(data => {
      const list = document.getElementById('user-discussions-list');
      list.innerHTML = '';
      if (!data.chats.length) {
        list.innerHTML = '<div class="text-muted text-center py-4">No discussions yet.</div>';
        return;
      }
      // Deduplicate by seller.id, keeping only the most recent chat per seller
      const seenSellers = new Set();
      const uniqueChats = [];
      for (const chat of data.chats) {
        const sellerId = chat.seller && chat.seller.id;
        if (sellerId && !seenSellers.has(sellerId)) {
          seenSellers.add(sellerId);
          uniqueChats.push(chat);
        }
      }
      uniqueChats.forEach(chat => {
        // ... existing code ...
      });
      // Mark as read function
      window.markSubuserRead = function(chatId, subuserId) {
        // ... existing code ...
      }
      // After rendering all items, if coming from a notification, open the correct chat
      if (notifChatId) {
        // Find the chat and seller info
        const chat = data.chats.find(c => String(c.id) === String(notifChatId));
        if (chat) {
          const seller = chat.seller;
          setTimeout(() => {
            // --- SET THE SUBUSER ID HERE BEFORE OPENING THE MODAL ---
            window.currentDirectSubuserId = notifSubuserId || null;
            markSubuserRead(chat.id, notifSubuserId || null);
            window.openDirectChatModal(chat.id, seller.username, seller.avatar_url, seller.id, notifSubuserId || null);
          }, 400);
        }
      }
    });
});
</script> 
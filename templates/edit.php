<!DOCTYPE html>
<html>
<head>
    <title>Edit Paste</title>
    <link rel="stylesheet" href="/web/style.css">
</head>
<body>
    <main>
        <form id="editForm" action="/<?php echo htmlspecialchars($uuid); ?>" method="PUT">
            <textarea name="content"><?php echo htmlspecialchars($note['content']); ?></textarea>
            <div class="controls">
                <select name="file_extension">
                    <option value="">Plain text</option>
                    <option value="php">PHP</option>
                    <option value="js">JavaScript</option>
                    <option value="py">Python</option>
                    <option value="md">Markdown</option>
                </select>
                <button type="submit">Update Paste</button>
                <button type="button" id="deleteButton" style="background-color: red; color: white;">Delete Paste</button>
            </div>
        </form>
    </main>
    <script>
        document.getElementById('editForm').addEventListener('submit', function(e) {
            e.preventDefault();
            fetch(this.action, {
                method: 'PUT',
                body: new FormData(this)
            }).then(response => {
                if (response.ok) {
                    window.location.href = '/<?php echo htmlspecialchars($uuid); ?>';
                }
            });
        });

        document.getElementById('deleteButton').addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this paste?')) {
                fetch('/<?php echo htmlspecialchars($uuid); ?>', {
                    method: 'DELETE'
                }).then(response => {
                    if (response.ok) {
                        window.location.href = '/<?php echo htmlspecialchars($uuid); ?>';
                    }
                });
            }
        });
    </script>
</body>
</html>

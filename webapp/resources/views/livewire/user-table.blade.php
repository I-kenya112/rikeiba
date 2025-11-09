<div class="p-6 bg-white shadow rounded-lg">
    <h2 class="text-xl font-semibold mb-4">ユーザー一覧</h2>
    <table class="min-w-full border border-gray-300">
        <thead>
            <tr class="bg-gray-100">
                <th class="border px-4 py-2">ID</th>
                <th class="border px-4 py-2">名前</th>
                <th class="border px-4 py-2">メール</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
                <tr>
                    <td class="border px-4 py-2">{{ $user->id }}</td>
                    <td class="border px-4 py-2">{{ $user->name }}</td>
                    <td class="border px-4 py-2">{{ $user->email }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

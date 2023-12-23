<div class="max-w-5xl m-auto bg-white rounded-lg shadow-md p-4">
    <h2 class="text-2xl font-bold mb-4">Listado de Chips Consumidos por Inspector</h2>

    @if($chipsConsumidos->count() > 0)
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inspector</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ubicación</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Grupo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha 2</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($chipsConsumidos as $chip)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $chip->id }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $chip->idUsuario }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $chip->estado }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $chip->ubicacion }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $chip->grupo }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $chip->created_at }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $chip->updated_at }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>No hay chips consumidos por inspectores.</p>
    @endif
</div>

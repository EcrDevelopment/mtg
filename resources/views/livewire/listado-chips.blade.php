<div>
    <div class="sm:px-6 w-full pt-12 pb-4">
        <x-custom-table>
            <x-slot name="titulo">
                <h2 class="text-indigo-600 font-bold text-3xl uppercase">
                    <i class="fa-solid fa-file-circle-check fa-xl text-indigo-600"></i>
                    &nbsp;Listado de Chips
                </h2>
            </x-slot>
            <x-slot name="btnAgregar" class="mt-6 ">
            </x-slot>
            <x-slot name="contenido">
                @if ($chipsConsumidos->count() > 0)
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    ID</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Inspector
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Servicio
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Estado
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Ubicación
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Grupo
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Fecha
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($chipsConsumidos as $chip)
                                <tr>
                                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                        <div class="flex items-center">
                                            <p class="text-indigo-900 p-1 bg-indigo-200 rounded-md">
                                                {{ $chip->id }}
                                            </p>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ $chip->nombreInspector }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                         {{-- @if ($chip->servicioDescripcion)
                                                <p
                                                    class="text-sm leading-none text-gray-600 ml-2 p-2 bg-green-200 rounded-full">
                                                    {{ $chip->servicioDescripcion }}
                                                </p>
                                            @else
                                                <p class="text-sm leading-none text-gray-600 ml-2">
                                                    Sin Datos
                                                </p>
                                            @endif--}}
                                            
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($chip->estado == 4)
                                            Consumido
                                        @else
                                            {{ $chip->estado }}
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ $chip->ubicacion }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ $chip->grupo }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ $chip->created_at }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p>No hay chips consumidos por inspectores.</p>
                @endif
            </x-slot>
        </x-custom-table>
    </div>
</div>
